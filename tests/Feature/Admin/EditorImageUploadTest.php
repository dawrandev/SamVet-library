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
