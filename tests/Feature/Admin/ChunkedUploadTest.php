<?php

use App\Models\AdminActivityLog;
use App\Models\Article;
use App\Models\Audiobook;
use App\Models\AudioTrack;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Dissertation;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\UploadSession;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoTrack;
use App\Services\ChunkedUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
});

/** A minimal file real content-sniffers recognize as the given kind. */
function chunkedUploadFakeContent(string $kind): string
{
    return match ($kind) {
        'pdf' => '%PDF-1.4'.str_repeat('A', 2048),
        'video' => "\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2avc1mp41".str_repeat('A', 2048),
        // ID3v2 header followed by repeated MPEG-1 Layer III frame syncs —
        // enough for the assembled file to be sniffed as audio/mpeg.
        'audio' => "ID3\x04\x00\x00\x00\x00\x00\x00".str_repeat("\xFF\xFB\x90\x00", 512),
    };
}

/** Uploads $content to $token in $chunkSize pieces via the real HTTP endpoint, returns the last response. */
function chunkedUploadPostChunks(string $token, string $content, int $chunkSize = 1024)
{
    $offset = 0;
    $index = 0;
    $response = null;

    while ($offset < strlen($content)) {
        $piece = substr($content, $offset, $chunkSize);
        $response = test()->post(route('admin.uploads.chunk', $token), [
            'index' => $index,
            'file' => UploadedFile::fake()->createWithContent("chunk-{$index}", $piece),
        ]);
        $offset += $chunkSize;
        $index++;
    }

    return $response;
}

beforeEach(fn () => actingAsAdmin());

it('starts a session and reports the expected chunk count', function () {
    $res = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'book.pdf',
        'total_size' => 12 * 1024 * 1024,
        'kind' => 'pdf',
    ]);

    $res->assertOk()->assertJsonStructure(['token', 'chunk_size', 'total_chunks']);
    expect(UploadSession::where('token', $res->json('token'))->exists())->toBeTrue();
});

it('rejects a declared total_size larger than the kind allows', function () {
    $res = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'huge.pdf',
        'total_size' => 973_000 * 1024, // just over the 950MB (972800 KB) cap
        'kind' => 'pdf',
    ]);

    $res->assertStatus(422)->assertJsonValidationErrors('total_size');
});

it('assembles a file across chunks and marks the session assembled', function () {
    $content = chunkedUploadFakeContent('pdf');
    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'book.pdf',
        'total_size' => strlen($content),
        'kind' => 'pdf',
    ])->json();

    $res = chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size']);

    $res->assertOk()->assertJson(['status' => 'assembled']);
    $session = UploadSession::where('token', $start['token'])->first();
    expect($session->status)->toBe('assembled')
        ->and(Storage::disk('local')->size($session->assembled_path))->toBe(strlen($content));
});

it('rejects a chunk that arrives out of order', function () {
    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'book.pdf', 'total_size' => 2048, 'kind' => 'pdf',
    ])->json();

    $res = $this->post(route('admin.uploads.chunk', $start['token']), [
        'index' => 1, // should be 0 first
        'file' => UploadedFile::fake()->createWithContent('chunk', 'abc'),
    ]);

    $res->assertStatus(409);
});

it('404s when an admin tries to upload a chunk to another admin\'s session', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);
    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'book.pdf', 'total_size' => 2048, 'kind' => 'pdf',
    ])->json();

    $intruder = User::factory()->create();
    $this->actingAs($intruder);

    $res = $this->post(route('admin.uploads.chunk', $start['token']), [
        'index' => 0,
        'file' => UploadedFile::fake()->createWithContent('chunk', 'abc'),
    ]);

    $res->assertNotFound();
});

it('rejects a file whose assembled content does not match the declared kind', function () {
    $content = 'this is plainly not a pdf, just text'.str_repeat('x', 2048);
    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'fake.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();

    $res = chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size']);

    $res->assertStatus(409);
    expect(UploadSession::where('token', $start['token'])->exists())->toBeFalse(); // discarded, not left dangling
});

it('lazily cleans up an admin\'s own stale sessions when starting a new upload', function () {
    $admin = auth('web')->user();
    $stale = UploadSession::create([
        'token' => (string) Str::uuid(),
        'admin_id' => $admin->id,
        'kind' => 'pdf',
        'original_filename' => 'old.pdf',
        'total_size' => 1024,
        'total_chunks' => 1,
        'next_chunk_index' => 0,
        'status' => 'uploading',
    ]);
    // created_at isn't mass-assignable (by design) — backdate it directly.
    $stale->forceFill(['created_at' => now()->subHours(25)])->save();

    $this->postJson(route('admin.uploads.start'), [
        'filename' => 'new.pdf', 'total_size' => 1024, 'kind' => 'pdf',
    ])->assertOk();

    expect(UploadSession::whereKey($stale->id)->exists())->toBeFalse();
});

it('creates a Book via a chunked-uploaded PDF, landing the file exactly where a direct upload would', function () {
    $type = BookType::factory()->create();
    $content = chunkedUploadFakeContent('pdf');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'kitob.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.books.store'), [
        'title' => 'Chunked yuklangan kitob',
        'book_type_id' => $type->id,
        'electronic_file_token' => $start['token'],
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Chunked yuklangan kitob');
    $admin = auth('web')->user();
    expect($book)->not->toBeNull()
        ->and($book->electronic_file)->not->toBeNull()
        ->and(str_starts_with($book->electronic_file, 'books/electronic/'))->toBeTrue()
        ->and(Storage::disk('local')->exists($book->electronic_file))->toBeTrue()
        ->and(Storage::disk('local')->size($book->electronic_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse() // single-use
        ->and(Storage::disk('local')->exists("chunk-uploads/{$admin->id}/{$start['token']}"))->toBeFalse() // no leftover empty dir
        ->and(AdminActivityLog::where('subject_type', 'Book')->where('subject_id', $book->id)->exists())->toBeTrue(); // audit trail still fires
});

it('creates a Dissertation via a chunked-uploaded PDF, landing the file exactly where a direct upload would', function () {
    $content = chunkedUploadFakeContent('pdf');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'dissertatsiya.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.dissertations.store'), [
        'title' => 'Chunked yuklangan dissertatsiya',
        'electronic_file_token' => $start['token'],
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Chunked yuklangan dissertatsiya');

    expect($dissertation)->not->toBeNull()
        ->and($dissertation->electronic_file)->not->toBeNull()
        ->and(str_starts_with($dissertation->electronic_file, 'dissertations/electronic/'))->toBeTrue()
        ->and(Storage::disk('local')->exists($dissertation->electronic_file))->toBeTrue()
        ->and(Storage::disk('local')->size($dissertation->electronic_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse(); // single-use
});

it('creates an Avtoreferat via a chunked-uploaded PDF, landing the file exactly where a direct upload would', function () {
    $content = chunkedUploadFakeContent('pdf');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'avtoreferat.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.avtoreferats.store'), [
        'title' => 'Chunked yuklangan avtoreferat',
        'advisor' => 'Ilmiy rahbar',
        'electronic_file_token' => $start['token'],
    ])->assertRedirect();

    $avtoreferat = Avtoreferat::firstWhere('title', 'Chunked yuklangan avtoreferat');

    expect($avtoreferat)->not->toBeNull()
        ->and($avtoreferat->electronic_file)->not->toBeNull()
        ->and(str_starts_with($avtoreferat->electronic_file, 'avtoreferats/electronic/'))->toBeTrue()
        ->and(Storage::disk('local')->exists($avtoreferat->electronic_file))->toBeTrue()
        ->and(Storage::disk('local')->size($avtoreferat->electronic_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse();
});

it('creates an Article via a chunked-uploaded PDF', function () {
    $content = chunkedUploadFakeContent('pdf');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'maqola.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.articles.store'), [
        'title' => 'Chunked yuklangan maqola',
        'external_journal_name' => 'Tashqi jurnal',
        'electronic_file_token' => $start['token'],
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Chunked yuklangan maqola');

    expect($article)->not->toBeNull()
        ->and(str_starts_with($article->electronic_file, 'articles/electronic/'))->toBeTrue()
        ->and(Storage::disk('local')->size($article->electronic_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse();
});

it('creates a JournalIssue via a chunked-uploaded PDF', function () {
    $journal = Journal::factory()->create();
    $content = chunkedUploadFakeContent('pdf');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'son.pdf', 'total_size' => strlen($content), 'kind' => 'pdf',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.journals.issues.store', $journal), [
        'year' => 2026,
        'issue_number' => '7',
        'electronic_file_token' => $start['token'],
    ])->assertRedirect();

    $issue = JournalIssue::where('journal_id', $journal->id)->first();

    expect($issue)->not->toBeNull()
        ->and(str_starts_with($issue->electronic_file, 'journals/electronic/'))->toBeTrue()
        ->and(Storage::disk('local')->size($issue->electronic_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse();
});

it('creates an AudioTrack via a chunked-uploaded audio file', function () {
    $audiobook = Audiobook::factory()->create();
    $content = chunkedUploadFakeContent('audio');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'trek.mp3', 'total_size' => strlen($content), 'kind' => 'audio',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.audiobooks.tracks.store', $audiobook), [
        'title' => 'Chunked yuklangan trek',
        'audio_file_token' => $start['token'],
    ])->assertRedirect();

    $track = AudioTrack::firstWhere('title', 'Chunked yuklangan trek');

    expect($track)->not->toBeNull()
        ->and(str_starts_with($track->audio_file, 'audiobooks/audio/'))->toBeTrue()
        ->and(Storage::disk('local')->size($track->audio_file))->toBe(strlen($content))
        ->and(UploadSession::where('token', $start['token'])->exists())->toBeFalse();
});

it('rejects a Dissertation whose upload token was never started', function () {
    $this->post(route('admin.dissertations.store'), [
        'title' => 'Soxta token',
        'electronic_file_token' => (string) Str::uuid(),
    ])->assertSessionHasErrors('electronic_file_token');
});

it('creates a VideoTrack via a chunked-uploaded video, landing the file exactly where a direct upload would', function () {
    $video = Video::factory()->create();
    $content = chunkedUploadFakeContent('video');

    $start = $this->postJson(route('admin.uploads.start'), [
        'filename' => 'video.mp4', 'total_size' => strlen($content), 'kind' => 'video',
    ])->json();
    chunkedUploadPostChunks($start['token'], $content, (int) $start['chunk_size'])->assertJson(['status' => 'assembled']);

    $this->post(route('admin.videos.tracks.store', $video), [
        'title' => '1-qism',
        'video_file_token' => $start['token'],
    ])->assertRedirect();

    $track = VideoTrack::where('video_id', $video->id)->where('title', '1-qism')->first();
    expect($track)->not->toBeNull()
        ->and($track->video_file)->not->toBeNull()
        ->and(str_starts_with($track->video_file, 'videos/video/'))->toBeTrue()
        ->and(Storage::disk('local')->exists($track->video_file))->toBeTrue()
        ->and(Storage::disk('local')->size($track->video_file))->toBe(strlen($content));
});

it('rejects a book form submitted with a token for a wrong/unclaimed session', function () {
    $type = BookType::factory()->create();

    $this->from(route('admin.books.create'))
        ->post(route('admin.books.store'), [
            'title' => 'X',
            'book_type_id' => $type->id,
            'electronic_file_token' => (string) Str::uuid(), // never started
        ])
        ->assertSessionHasErrors('electronic_file_token');
});

/**
 * Disk-full handling.
 *
 * stream_copy_to_stream() does not raise when the filesystem runs out of room —
 * it returns a short count. That number used to be discarded, so the chunk
 * "succeeded", next_chunk_index advanced, and the truncation was sealed in at a
 * byte offset nothing downstream looked at: finish() re-validates the type, and
 * a truncated PDF still starts with %PDF.
 *
 * UploadedFile::fake()->create() reproduces it exactly — it reports a declared
 * size while the file behind it is empty, which is the same mismatch a full
 * disk produces.
 */
it('rejects a chunk that could not be written in full, instead of storing a truncated file', function () {
    $content = chunkedUploadFakeContent('pdf');

    $start = test()->post(route('admin.uploads.start'), [
        'filename' => 'kitob.pdf',
        'total_size' => strlen($content),
        'kind' => 'pdf',
    ])->json();

    $response = test()->post(route('admin.uploads.chunk', $start['token']), [
        'index' => 0,
        // Declares 64 KB, carries nothing — a write that came up short.
        'file' => UploadedFile::fake()->create('chunk-0', 64),
    ]);

    $response->assertStatus(409);
    expect($response->json('message'))->toContain('joy yetmadi');

    // The session must not have moved on: the browser retries the same index
    // up to three times, and it can only do that if the server did not count
    // the failed attempt.
    expect(UploadSession::where('token', $start['token'])->value('next_chunk_index'))->toBe(0);
});

it('cuts a half-written chunk back off, so a retry cannot double-append', function () {
    // Driven through the service rather than the endpoint: the point of this
    // test is a chunk that reports one size and carries fewer bytes, and
    // sizeToReport does not survive the HTTP test client's file marshalling.
    // A hand-built session keeps total_chunks above 1 so finish() — and its
    // own size check — stays out of the way.
    $admin = actingAsAdmin();

    $session = UploadSession::create([
        'token' => (string) Str::uuid(),
        'admin_id' => $admin->id,
        'kind' => 'pdf',
        'original_filename' => 'kitob.pdf',
        'total_size' => 20_000_000,
        'total_chunks' => 4,
        'next_chunk_index' => 0,
        'status' => 'uploading',
    ]);

    $dir = "chunk-uploads/{$admin->id}/{$session->token}";
    Storage::disk('local')->makeDirectory($dir);

    $service = app(ChunkedUploadService::class);

    $good = str_repeat('A', 4096);
    $service->storeChunk($session->fresh(), 0, UploadedFile::fake()->createWithContent('chunk-0', $good));

    $partPath = $dir.'/assembling.part';
    expect(Storage::disk('local')->size($partPath))->toBe(strlen($good));

    // Real bytes, but fewer than the chunk claims — what a full disk produces.
    $short = UploadedFile::fake()->createWithContent('chunk-1', str_repeat('B', 100));
    $short->sizeToReport = 4096;

    expect(fn () => $service->storeChunk($session->fresh(), 1, $short))
        ->toThrow(RuntimeException::class);

    // Those 100 bytes must be gone: the handle appends, so leaving them behind
    // would mean the browser's retry stacks another copy on top of them.
    expect(Storage::disk('local')->size($partPath))->toBe(strlen($good))
        ->and($session->fresh()->next_chunk_index)->toBe(1);
});
it('refuses to assemble a file whose bytes fall short of the declared size', function () {
    // The chunk count can be right while the bytes are not: one chunk is
    // expected here, and one arrives — just a much smaller one.
    $start = test()->post(route('admin.uploads.start'), [
        'filename' => 'kitob.pdf',
        'total_size' => 500_000,
        'kind' => 'pdf',
    ])->json();

    expect($start['total_chunks'])->toBe(1);

    $response = test()->post(route('admin.uploads.chunk', $start['token']), [
        'index' => 0,
        'file' => UploadedFile::fake()->createWithContent('chunk-0', chunkedUploadFakeContent('pdf')),
    ]);

    $response->assertStatus(409);
    expect($response->json('message'))->toContain('to\'liq yig\'ilmadi');

    // Nothing half-finished is left lying around to be claimed later.
    expect(UploadSession::where('token', $start['token'])->exists())->toBeFalse();
});
