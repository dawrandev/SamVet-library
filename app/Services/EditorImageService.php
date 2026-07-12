<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores images inserted inline into a TinyMCE rich-text editor (news/page
 * body). Shared across every admin form that uses <x-admin.form.rich-editor>
 * — the image isn't tied to a specific model, so it has no repository.
 */
class EditorImageService
{
    public function store(UploadedFile $file): string
    {
        $path = $file->store('editor/'.date('Y/m'), 'public');

        return Storage::disk('public')->url($path);
    }
}
