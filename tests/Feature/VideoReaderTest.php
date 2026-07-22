<?php

use App\Models\Video;
use App\Models\VideoTrack;
use Illuminate\Support\Facades\Storage;

it('redirects a guest from the watch page to the reader login', function () {
    Storage::fake('local');
    $video = Video::factory()->create();
    VideoTrack::factory()->for($video)->create();

    $this->get(route('watch.video', $video->slug))->assertRedirect(route('reader.login'));
});

it('redirects a guest from the raw track stream to login (no unauthenticated access)', function () {
    Storage::fake('local');
    $video = Video::factory()->create();
    $track = VideoTrack::factory()->for($video)->create();

    $this->get(route('watch.video.file', [$video->slug, $track->id]))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the watch page', function () {
    Storage::fake('local');
    actingAsReader();
    $video = Video::factory()->create();
    VideoTrack::factory()->for($video)->create();

    $this->get(route('watch.video', $video->slug))->assertOk();
});

it('404s when a reader opens a video that has no tracks', function () {
    actingAsReader();
    $video = Video::factory()->create();

    $this->get(route('watch.video', $video->slug))->assertNotFound();
});

it('streams the full track inline, no-store, and never as a download', function () {
    Storage::fake('local');
    $path = 'videos/video/x.mp4';
    Storage::disk('local')->put($path, 'full mp4 content');

    actingAsReader();
    $video = Video::factory()->create();
    $track = VideoTrack::factory()->for($video)->create(['video_file' => $path]);

    $res = $this->get(route('watch.video.file', [$video->slug, $track->id]));

    $res->assertOk();
    expect($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment')
        ->and($res->headers->get('cache-control'))->toContain('no-store')
        ->and($res->headers->get('accept-ranges'))->toBe('bytes')
        ->and($res->streamedContent())->toBe('full mp4 content');
});

it('serves a 206 partial response honoring a byte Range request, so the player can seek', function () {
    Storage::fake('local');
    $path = 'videos/video/x.mp4';
    Storage::disk('local')->put($path, '0123456789');

    actingAsReader();
    $video = Video::factory()->create();
    $track = VideoTrack::factory()->for($video)->create(['video_file' => $path]);

    $res = $this->withHeaders(['Range' => 'bytes=2-5'])
        ->get(route('watch.video.file', [$video->slug, $track->id]));

    $res->assertStatus(206);
    expect($res->headers->get('content-range'))->toBe('bytes 2-5/10')
        ->and($res->headers->get('content-length'))->toBe('4')
        ->and($res->streamedContent())->toBe('2345');
});

it('404s when the track does not belong to the given video slug', function () {
    Storage::fake('local');
    actingAsReader();
    $video = Video::factory()->create();
    VideoTrack::factory()->for($video)->create();

    $otherVideo = Video::factory()->create();
    $otherTrack = VideoTrack::factory()->for($otherVideo)->create();
    Storage::disk('local')->put($otherTrack->video_file, 'other');

    $this->get(route('watch.video.file', [$video->slug, $otherTrack->id]))->assertNotFound();
});
