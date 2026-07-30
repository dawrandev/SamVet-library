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
     * @throws \RuntimeException  chunk arrived out of order, or the session is already assembled
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
        $in = fopen($chunk->getRealPath(), 'rb');
        $out = fopen($target, 'ab');

        try {
            stream_copy_to_stream($in, $out);
        } finally {
            fclose($in);
            fclose($out);
        }

        return $this->sessions->update($session, ['next_chunk_index' => $session->next_chunk_index + 1]);
    }

    /**
     * @throws \RuntimeException  not all chunks arrived yet, or the assembled file fails re-validation
     */
    public function finish(UploadSession $session): UploadSession
    {
        if ($session->next_chunk_index !== $session->total_chunks) {
            throw new \RuntimeException('Barcha bo\'laklar hali yetib kelmagan.');
        }

        $relativePath = $this->assemblingPath($session);
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);

        // Real, post-assembly validation against the actual file content —
        // stronger than a client-declared filename/MIME, and mirrors the
        // matching FormRequest's rule for this field (see ChunkedUploadKind).
        $validator = Validator::make(
            ['file' => new File($absolutePath)],
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
     * @throws \RuntimeException  token unknown, belongs to someone else, wrong kind, or not yet assembled
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
