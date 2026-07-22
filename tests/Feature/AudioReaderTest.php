<?php

use App\Models\Audiobook;
use App\Models\AudioTrack;
use Illuminate\Support\Facades\Storage;

it('redirects a guest from the listen page to the reader login', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();
    AudioTrack::factory()->for($audiobook)->create();

    $this->get(route('listen.audiobook', $audiobook->slug))->assertRedirect(route('reader.login'));
});

it('redirects a guest from the raw track stream to login (no unauthenticated access)', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($audiobook)->create();

    $this->get(route('listen.audiobook.file', [$audiobook->slug, $track->id]))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the listen page', function () {
    Storage::fake('local');
    actingAsReader();
    $audiobook = Audiobook::factory()->create();
    AudioTrack::factory()->for($audiobook)->create();

    $this->get(route('listen.audiobook', $audiobook->slug))->assertOk();
});

it('404s when a reader opens an audiobook that has no tracks', function () {
    actingAsReader();
    $audiobook = Audiobook::factory()->create();

    $this->get(route('listen.audiobook', $audiobook->slug))->assertNotFound();
});

it('streams the full track inline, no-store, and never as a download', function () {
    Storage::fake('local');
    $path = 'audiobooks/audio/x.mp3';
    Storage::disk('local')->put($path, 'full mp3 content');

    actingAsReader();
    $audiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($audiobook)->create(['audio_file' => $path]);

    $res = $this->get(route('listen.audiobook.file', [$audiobook->slug, $track->id]));

    $res->assertOk();
    expect($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment')
        ->and($res->headers->get('cache-control'))->toContain('no-store')
        ->and($res->headers->get('accept-ranges'))->toBe('bytes')
        ->and($res->streamedContent())->toBe('full mp3 content');
});

it('serves a 206 partial response honoring a byte Range request, so the player can seek', function () {
    Storage::fake('local');
    $path = 'audiobooks/audio/x.mp3';
    Storage::disk('local')->put($path, '0123456789');

    actingAsReader();
    $audiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($audiobook)->create(['audio_file' => $path]);

    $res = $this->withHeaders(['Range' => 'bytes=2-5'])
        ->get(route('listen.audiobook.file', [$audiobook->slug, $track->id]));

    $res->assertStatus(206);
    expect($res->headers->get('content-range'))->toBe('bytes 2-5/10')
        ->and($res->headers->get('content-length'))->toBe('4')
        ->and($res->streamedContent())->toBe('2345');
});

it('404s when the track does not belong to the given audiobook slug', function () {
    Storage::fake('local');
    actingAsReader();
    $audiobook = Audiobook::factory()->create();
    AudioTrack::factory()->for($audiobook)->create();

    $otherAudiobook = Audiobook::factory()->create();
    $otherTrack = AudioTrack::factory()->for($otherAudiobook)->create();
    Storage::disk('local')->put($otherTrack->audio_file, 'other');

    $this->get(route('listen.audiobook.file', [$audiobook->slug, $otherTrack->id]))->assertNotFound();
});
