<?php

use App\Models\AdminActivityLog;
use App\Models\Book;
use App\Models\BookType;
use App\Models\UploadSession;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/** A minimal file real content-sniffers recognize as the given kind. */
function chunkedUploadFakeContent(string $kind): string
{
    return match ($kind) {
        'pdf' => '%PDF-1.4'.str_repeat('A', 2048),
        'video' => "\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2avc1mp41".str_repeat('A', 2048),
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
        'token' => (string) \Illuminate\Support\Str::uuid(),
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
            'electronic_file_token' => (string) \Illuminate\Support\Str::uuid(), // never started
        ])
        ->assertSessionHasErrors('electronic_file_token');
});
