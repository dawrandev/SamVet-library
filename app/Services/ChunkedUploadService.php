<?php

namespace App\Services;

use App\Enums\ChunkedUploadKind;
use App\Models\UploadSession;
use App\Models\User;
use App\Repositories\Contracts\UploadSessionRepositoryInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Assembles a large file (PDF/video/audio) uploaded as a sequence of small
 * chunk POSTs — see ARCHITECTURE.md 4.11-style doc comment in
 * ChunkedUploadController. Chunks are received strictly in order (index 0, 1,
 * 2, ...) and appended to a single per-session file; there is no per-chunk
 * filename ever derived from client input, so there's nothing to path-traverse.
 *
 * Every large-file field this backs (electronic_file, video_file, ...) keeps
 * working via a direct, non-chunked upload for files under the JS-side size
 * threshold — this service is only ever exercised for genuinely large files.
 */
class ChunkedUploadService
{
    private const DISK = 'local';

    private const BASE_DIR = 'chunk-uploads';

    public const CHUNK_SIZE = 5 * 1024 * 1024; // 5 MB

    private const STALE_AFTER_HOURS = 24;

    public function __construct(
        private readonly UploadSessionRepositoryInterface $sessions,
    ) {}

    public function findOwnedSession(string $token, int $adminId): ?UploadSession
    {
        $session = $this->sessions->findByToken($token);

        return $session && $session->admin_id === $adminId ? $session : null;
    }

    public function start(string $filename, int $totalSizeBytes, ChunkedUploadKind $kind, User $admin): UploadSession
    {
        $this->cleanupStaleFor($admin);

        $token = (string) Str::uuid();
        $totalChunks = (int) ceil($totalSizeBytes / self::CHUNK_SIZE);

        Storage::disk(self::DISK)->makeDirectory($this->sessionDir($admin->id, $token));

        return $this->sessions->create([
            'token' => $token,
            'admin_id' => $admin->id,
            'kind' => $kind,
            'original_filename' => $filename,
            'total_size' => $totalSizeBytes,
            'total_chunks' => max(1, $totalChunks),
            'next_chunk_index' => 0,
            'status' => 'uploading',
        ]);
    }

    /**
     * @throws \RuntimeException chunk arrived out of order, or the session is already assembled
     */
    public function storeChunk(UploadSession $session, int $index, UploadedFile $chunk): UploadSession
    {
        if ($session->status !== 'uploading') {
            throw new \RuntimeException('Bu yuklash sessiyasi allaqachon yakunlangan.');
        }

        if ($index !== $session->next_chunk_index) {
            throw new \RuntimeException("Kutilmagan bo'lak tartibi (kutilgan: {$session->next_chunk_index}, kelgan: {$index}).");
        }

        $target = Storage::disk(self::DISK)->path($this->assemblingPath($session));

        // Where the file ends before this chunk. A short write has to be undone
        // back to exactly here: the browser re-sends a failed chunk up to three
        // times (upload-form.js), the handle is opened in append mode, and
        // without a rewind each retry would stack its partial bytes on top of
        // the last one — turning a recoverable failure into a corrupt file.
        $offsetBefore = is_file($target) ? (int) filesize($target) : 0;

        $expected = (int) $chunk->getSize();

        $in = @fopen($chunk->getRealPath(), 'rb');
        $out = @fopen($target, 'ab');

        if ($in === false || $out === false) {
            if (is_resource($in)) {
                fclose($in);
            }

            if (is_resource($out)) {
                fclose($out);
            }

            throw new \RuntimeException('Faylni yozib bo\'lmadi — serverda joy yoki ruxsat yetishmayapti.');
        }

        try {
            $written = stream_copy_to_stream($in, $out);
        } finally {
            fclose($in);
            fclose($out);
        }

        // The failure this exists for: a full disk. stream_copy_to_stream()
        // reports a short count rather than raising, and the old code discarded
        // that number entirely — so the chunk "succeeded", next_chunk_index
        // advanced, and the truncation was baked in at a byte offset nothing
        // downstream could detect. finish() re-validates the type, but a
        // truncated PDF still begins with %PDF and passes.
        if ($written !== $expected) {
            $this->rewind($target, $offsetBefore);

            throw new \RuntimeException(sprintf(
                'Serverda joy yetmadi — bo\'lak to\'liq yozilmadi (kutilgan: %s, yozilgan: %s). Fayl saqlanmadi.',
                $this->formatBytes($expected),
                $this->formatBytes(max(0, (int) $written)),
            ));
        }

        return $this->sessions->update($session, ['next_chunk_index' => $session->next_chunk_index + 1]);
    }

    /** Cuts a half-written chunk back off, so the client's retry starts clean. */
    private function rewind(string $path, int $length): void
    {
        $handle = @fopen($path, 'r+b');

        if ($handle === false) {
            return;
        }

        ftruncate($handle, $length);
        fclose($handle);
    }

    private function formatBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024, 1).' KB';
    }

    /**
     * @throws \RuntimeException not all chunks arrived yet, or the assembled file fails re-validation
     */
    public function finish(UploadSession $session): UploadSession
    {
        if ($session->next_chunk_index !== $session->total_chunks) {
            throw new \RuntimeException('Barcha bo\'laklar hali yetib kelmagan.');
        }

        $relativePath = $this->assemblingPath($session);
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);

        // The count of chunks matching is not the same as the bytes matching.
        // storeChunk() catches a short write as it happens, but only for the
        // chunk in front of it; this compares the finished file against the
        // size the browser declared at start(), which also catches a client
        // that sent the right number of undersized pieces.
        $assembledSize = is_file($absolutePath) ? (int) filesize($absolutePath) : 0;

        if ($assembledSize !== (int) $session->total_size) {
            $this->discard($session);

            throw new \RuntimeException(sprintf(
                'Fayl to\'liq yig\'ilmadi (kutilgan: %s, yig\'ilgan: %s). Qayta yuklang.',
                $this->formatBytes((int) $session->total_size),
                $this->formatBytes($assembledSize),
            ));
        }

        // Real, post-assembly validation against the actual file content —
        // stronger than a client-declared filename/MIME, and mirrors the
        // matching FormRequest's rule for this field (see ChunkedUploadKind).
        //
        // Built as an UploadedFile (test mode: the bytes are already on disk,
        // there is no PHP upload to inspect) rather than a plain File, because
        // the Audio kind validates with `extensions:` — deliberately, since
        // libmagic misreads some ID3v2-tagged MP3s — and that rule reads
        // getClientOriginalExtension(), which a plain File does not carry. With
        // one it silently failed every audio assembly. `mimes:` for Pdf/Video
        // still inspects the content, so nothing is weakened here.
        $validator = Validator::make(
            ['file' => new UploadedFile($absolutePath, $session->original_filename, null, null, true)],
            ['file' => [$session->kind->validationRule()]],
        );

        if ($validator->fails()) {
            $this->discard($session);

            throw new \RuntimeException('Yuklangan fayl turi kutilganiga mos kelmadi.');
        }

        return $this->sessions->update($session, ['status' => 'assembled', 'assembled_path' => $relativePath]);
    }

    /**
     * Moves the assembled file into the caller's own permanent directory
     * (e.g. "books/electronic") — a fast filesystem move, not a copy, since
     * both live on the same 'local' disk — naming it with the original
     * file's real extension (Video allows several). Marks the session
     * consumed and cleans up its now-empty chunk directory; a token is
     * usable exactly once.
     *
     * @throws \RuntimeException token unknown, belongs to someone else, wrong kind, or not yet assembled
     */
    public function claimAndMove(string $token, ChunkedUploadKind $expectedKind, User $admin, string $destinationDir): string
    {
        $session = $this->sessions->findByToken($token);

        if (! $session || $session->admin_id !== $admin->id || $session->kind !== $expectedKind || $session->status !== 'assembled') {
            throw new \RuntimeException('Yuklash sessiyasi topilmadi yoki muddati tugagan.');
        }

        $extension = pathinfo($session->original_filename, PATHINFO_EXTENSION);
        $destination = $destinationDir.'/'.Str::random(40).'.'.$extension;

        Storage::disk(self::DISK)->move($session->assembled_path, $destination);
        Storage::disk(self::DISK)->deleteDirectory($this->sessionDir($session->admin_id, $session->token));
        $this->sessions->delete($session);

        return $destination;
    }

    /**
     * Same checks as claim() without consuming the session — used by
     * FormRequest validation so a bad/expired token surfaces as a normal
     * 422 validation error instead of an exception bubbling out of a Service.
     */
    public function isClaimable(string $token, ChunkedUploadKind $expectedKind, User $admin): bool
    {
        $session = $this->sessions->findByToken($token);

        return $session !== null
            && $session->admin_id === $admin->id
            && $session->kind === $expectedKind
            && $session->status === 'assembled';
    }

    private function cleanupStaleFor(User $admin): void
    {
        foreach ($this->sessions->staleForAdmin($admin->id, self::STALE_AFTER_HOURS) as $stale) {
            $this->discard($stale);
        }
    }

    private function discard(UploadSession $session): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->sessionDir($session->admin_id, $session->token));
        $this->sessions->delete($session);
    }

    private function sessionDir(int $adminId, string $token): string
    {
        return self::BASE_DIR."/{$adminId}/{$token}";
    }

    private function assemblingPath(UploadSession $session): string
    {
        return $this->sessionDir($session->admin_id, $session->token).'/assembling.part';
    }
}
