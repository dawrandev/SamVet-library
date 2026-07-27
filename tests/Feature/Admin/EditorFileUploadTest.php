<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('uploads a file attached via the rich-text editor and returns its url and original name', function () {
    Storage::fake('public');

    $response = $this->post(route('admin.editor-files.store'), [
        'file' => UploadedFile::fake()->create('qabul-tartibi.pdf', 100, 'application/pdf'),
    ]);

    $response->assertOk()->assertJsonStructure(['url', 'name']);
    expect($response->json('name'))->toBe('qabul-tartibi.pdf');

    $path = str($response->json('url'))->after('/storage/')->toString();
    Storage::disk('public')->assertExists($path);
    expect($path)->toStartWith('editor-files/');
});

it('reuses the same stored file when the identical document is uploaded again', function () {
    Storage::fake('public');

    $first = $this->post(route('admin.editor-files.store'), [
        'file' => UploadedFile::fake()->create('uz-hujjat.pdf', 100, 'application/pdf'),
    ])->json('url');

    $second = $this->post(route('admin.editor-files.store'), [
        'file' => UploadedFile::fake()->create('ru-hujjat.pdf', 100, 'application/pdf'),
    ])->json('url');

    expect($second)->toBe($first);
    expect(Storage::disk('public')->allFiles('editor-files'))->toHaveCount(1);
});

it('rejects a disallowed file type', function () {
    $this->post(route('admin.editor-files.store'), [
        'file' => UploadedFile::fake()->create('script.exe', 10),
    ])->assertSessionHasErrors('file');
});

it('blocks guests', function () {
    auth()->logout();

    $this->post(route('admin.editor-files.store'), [
        'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ])->assertRedirect(route('login'));
});
