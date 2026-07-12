<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('uploads an image pasted/inserted into the rich-text editor and returns its url', function () {
    Storage::fake('public');

    $response = $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->image('pasted.png', 10, 10),
    ]);

    $response->assertOk()->assertJsonStructure(['location']);

    $path = str($response->json('location'))->after('/storage/')->toString();
    Storage::disk('public')->assertExists($path);
    expect($path)->toStartWith('editor/');
});

it('reuses the same file when the identical image is uploaded again (uz/ru/kk paste each other)', function () {
    Storage::fake('public');

    // Same dimensions/content pasted under a different filename — as happens
    // when an admin pastes one illustration into all 3 language tabs.
    $first = $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->image('uz-paste.png', 10, 10),
    ])->json('location');

    $second = $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->image('ru-paste.png', 10, 10),
    ])->json('location');

    expect($second)->toBe($first);
    expect(Storage::disk('public')->allFiles('editor'))->toHaveCount(1);
});

it('rejects a non-image file', function () {
    $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
    ])->assertSessionHasErrors('file');
});

it('rejects an svg (stored-XSS risk)', function () {
    $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->create('malware.svg', 5, 'image/svg+xml'),
    ])->assertSessionHasErrors('file');
});

it('blocks guests', function () {
    auth()->logout();

    $this->post(route('admin.editor-images.store'), [
        'file' => UploadedFile::fake()->image('x.png'),
    ])->assertRedirect(route('login'));
});
