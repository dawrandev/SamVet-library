<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores images inserted inline into a TinyMCE rich-text editor (news/page
 * body). Shared across every admin form that uses <x-admin.form.rich-editor>
 * — the image isn't tied to a specific model, so it has no repository.
 *
 * Content-addressed by SHA-256: news/pages are filled in 3 languages, each
 * with its own editor instance, so the same illustration often gets pasted
 * 3 times. Naming the file after its content hash means the 2nd and 3rd
 * paste resolve to the file already on disk instead of writing duplicates.
 */
class EditorImageService
{
    public function store(UploadedFile $file): string
    {
        $path = 'editor/'.hash_file('sha256', $file->path()).'.'.$file->extension();

        if (! Storage::disk('public')->exists($path)) {
            $file->storeAs('editor', basename($path), 'public');
        }

        return Storage::disk('public')->url($path);
    }
}
