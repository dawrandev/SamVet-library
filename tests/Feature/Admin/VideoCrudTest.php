<?php

use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates a video', function () {
    Storage::fake('public');

    $this->post(route('admin.videos.store'), [
        'name' => 'Anatomiya darsligi',
        'author' => 'Prof. X',
        'annotation' => 'Video darslik haqida qisqacha.',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertRedirect();

    $video = Video::firstWhere('name', 'Anatomiya darsligi');
    expect($video)->not->toBeNull()
        ->and($video->author)->toBe('Prof. X')
        ->and($video->cover_image)->not->toBeNull()
        ->and($video->slug)->not->toBeEmpty();
});

it('requires a name', function () {
    $this->from(route('admin.videos.create'))
        ->post(route('admin.videos.store'), [])
        ->assertSessionHasErrors('name');
});

it('updates a video', function () {
    $video = Video::factory()->create(['name' => 'Eski nom']);

    $this->put(route('admin.videos.update', $video), [
        'name' => 'Yangi nom',
    ])->assertRedirect();

    expect($video->fresh()->name)->toBe('Yangi nom');
});

it('deletes a video and its tracks, removing files from disk', function () {
    Storage::fake('public');
    Storage::fake('local');

    $video = Video::factory()->create();
    Storage::disk('public')->put($video->cover_image = 'video-covers/x.jpg', 'fake');
    $video->save();

    $track = VideoTrack::factory()->for($video)->create();
    Storage::disk('local')->put($track->video_file, 'fake mp4');

    $this->delete(route('admin.videos.destroy', $video))->assertRedirect();

    $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    $this->assertDatabaseMissing('video_tracks', ['id' => $track->id]);
    Storage::disk('public')->assertMissing($video->cover_image);
    Storage::disk('local')->assertMissing($track->video_file);
});

it('adds a video track to a video', function () {
    Storage::fake('local');
    $video = Video::factory()->create();

    $this->post(route('admin.videos.tracks.store', $video), [
        'title' => '1-qism',
        'video_file' => UploadedFile::fake()->create('track.mp4', 5000, 'video/mp4'),
    ])->assertRedirect(route('admin.videos.show', $video));

    $track = $video->tracks()->first();
    expect($track)->not->toBeNull()
        ->and($track->title)->toBe('1-qism')
        ->and($track->video_file)->not->toBeNull();
    Storage::disk('local')->assertExists($track->video_file);
});

it('rejects a non-video file for a track', function () {
    $video = Video::factory()->create();

    $this->from(route('admin.videos.show', $video))
        ->post(route('admin.videos.tracks.store', $video), [
            'title' => '1-qism',
            'video_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('video_file');
});

it('updates a video track, replacing the file only when a new one is uploaded', function () {
    Storage::fake('local');
    $video = Video::factory()->create();
    $track = VideoTrack::factory()->for($video)->create(['title' => 'Eski nom', 'video_file' => 'videos/video/old.mp4']);
    Storage::disk('local')->put($track->video_file, 'old content');

    $this->put(route('admin.videos.tracks.update', [$video, $track]), [
        'title' => 'Yangi nom',
    ])->assertRedirect();

    $track->refresh();
    expect($track->title)->toBe('Yangi nom')
        ->and($track->video_file)->toBe('videos/video/old.mp4'); // unchanged — no new file uploaded
    Storage::disk('local')->assertExists($track->video_file);
});

it('deletes a video track, removing its file from disk', function () {
    Storage::fake('local');
    $video = Video::factory()->create();
    $track = VideoTrack::factory()->for($video)->create();
    Storage::disk('local')->put($track->video_file, 'fake mp4');

    $this->delete(route('admin.videos.tracks.destroy', [$video, $track]))->assertRedirect();

    $this->assertDatabaseMissing('video_tracks', ['id' => $track->id]);
    Storage::disk('local')->assertMissing($track->video_file);
});

it('404s when the track does not belong to the given video', function () {
    $video = Video::factory()->create();
    $otherVideo = Video::factory()->create();
    $track = VideoTrack::factory()->for($otherVideo)->create();

    $this->delete(route('admin.videos.tracks.destroy', [$video, $track]))->assertNotFound();
});

it('downloads an Excel export of the video list', function () {
    Video::factory()->count(2)->create();

    $response = $this->get(route('admin.videos.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});
