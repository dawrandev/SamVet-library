<?php

use App\Models\Audiobook;
use App\Models\AudioTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates an audiobook', function () {
    Storage::fake('public');

    $this->post(route('admin.audiobooks.store'), [
        'name' => 'Mehrobdan chayon',
        'author' => 'Abdulla Qodiriy',
        'annotation' => 'Roman haqida qisqacha.',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertRedirect();

    $audiobook = Audiobook::firstWhere('name', 'Mehrobdan chayon');
    expect($audiobook)->not->toBeNull()
        ->and($audiobook->author)->toBe('Abdulla Qodiriy')
        ->and($audiobook->cover_image)->not->toBeNull()
        ->and($audiobook->slug)->not->toBeEmpty();
});

it('requires a name', function () {
    $this->from(route('admin.audiobooks.create'))
        ->post(route('admin.audiobooks.store'), [])
        ->assertSessionHasErrors('name');
});

it('updates an audiobook', function () {
    $audiobook = Audiobook::factory()->create(['name' => 'Eski nom']);

    $this->put(route('admin.audiobooks.update', $audiobook), [
        'name' => 'Yangi nom',
    ])->assertRedirect();

    expect($audiobook->fresh()->name)->toBe('Yangi nom');
});

it('deletes an audiobook and its tracks, removing files from disk', function () {
    Storage::fake('public');
    Storage::fake('local');

    $audiobook = Audiobook::factory()->create();
    Storage::disk('public')->put($audiobook->cover_image = 'audiobook-covers/x.jpg', 'fake');
    $audiobook->save();

    $track = AudioTrack::factory()->for($audiobook)->create();
    Storage::disk('local')->put($track->audio_file, 'fake mp3');

    $this->delete(route('admin.audiobooks.destroy', $audiobook))->assertRedirect();

    $this->assertDatabaseMissing('audiobooks', ['id' => $audiobook->id]);
    $this->assertDatabaseMissing('audio_tracks', ['id' => $track->id]);
    Storage::disk('public')->assertMissing($audiobook->cover_image);
    Storage::disk('local')->assertMissing($track->audio_file);
});

it('adds an audio track to an audiobook', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();

    $this->post(route('admin.audiobooks.tracks.store', $audiobook), [
        'title' => '1-qism',
        'audio_file' => UploadedFile::fake()->create('track.mp3', 5000, 'audio/mpeg'),
    ])->assertRedirect(route('admin.audiobooks.show', $audiobook));

    $track = $audiobook->tracks()->first();
    expect($track)->not->toBeNull()
        ->and($track->title)->toBe('1-qism')
        ->and($track->audio_file)->not->toBeNull();
    Storage::disk('local')->assertExists($track->audio_file);
});

it('accepts an m4a (MPEG-4 audio) file for a track', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();

    $this->post(route('admin.audiobooks.tracks.store', $audiobook), [
        'title' => '1-qism',
        'audio_file' => UploadedFile::fake()->create('track.m4a', 5000, 'audio/mp4'),
    ])->assertRedirect(route('admin.audiobooks.show', $audiobook));

    $track = $audiobook->tracks()->first();
    expect($track)->not->toBeNull()
        ->and($track->audio_file)->not->toBeNull();
    Storage::disk('local')->assertExists($track->audio_file);
});

it('accepts a real mp3 even when its sniffed content type is generic (ID3v2 cover art confuses libmagic)', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();

    // Regression: validation used to be `mimes:...`, which re-detects the
    // type from the file's own bytes — real MP3s with ID3v2 tags (embedded
    // cover art especially) are routinely mis-sniffed as something generic
    // like application/octet-stream and got rejected outright. `extensions`
    // trusts the filename instead, so a mismatched sniffed type must not
    // block a genuinely .mp3-named upload anymore.
    $this->post(route('admin.audiobooks.tracks.store', $audiobook), [
        'title' => '1-qism',
        'audio_file' => UploadedFile::fake()->create('01. Yoqolgan dunyo.mp3', 5000, 'application/octet-stream'),
    ])->assertRedirect(route('admin.audiobooks.show', $audiobook));

    $track = $audiobook->tracks()->first();
    expect($track)->not->toBeNull();
    Storage::disk('local')->assertExists($track->audio_file);
});

it('rejects a non-audio file for a track', function () {
    $audiobook = Audiobook::factory()->create();

    $this->from(route('admin.audiobooks.show', $audiobook))
        ->post(route('admin.audiobooks.tracks.store', $audiobook), [
            'title' => '1-qism',
            'audio_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('audio_file');
});

it('updates an audio track, replacing the file only when a new one is uploaded', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($audiobook)->create(['title' => 'Eski nom', 'audio_file' => 'audiobooks/audio/old.mp3']);
    Storage::disk('local')->put($track->audio_file, 'old content');

    $this->put(route('admin.audiobooks.tracks.update', [$audiobook, $track]), [
        'title' => 'Yangi nom',
    ])->assertRedirect();

    $track->refresh();
    expect($track->title)->toBe('Yangi nom')
        ->and($track->audio_file)->toBe('audiobooks/audio/old.mp3'); // unchanged — no new file uploaded
    Storage::disk('local')->assertExists($track->audio_file);
});

it('deletes an audio track, removing its file from disk', function () {
    Storage::fake('local');
    $audiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($audiobook)->create();
    Storage::disk('local')->put($track->audio_file, 'fake mp3');

    $this->delete(route('admin.audiobooks.tracks.destroy', [$audiobook, $track]))->assertRedirect();

    $this->assertDatabaseMissing('audio_tracks', ['id' => $track->id]);
    Storage::disk('local')->assertMissing($track->audio_file);
});

it('404s when the track does not belong to the given audiobook', function () {
    $audiobook = Audiobook::factory()->create();
    $otherAudiobook = Audiobook::factory()->create();
    $track = AudioTrack::factory()->for($otherAudiobook)->create();

    $this->delete(route('admin.audiobooks.tracks.destroy', [$audiobook, $track]))->assertNotFound();
});
