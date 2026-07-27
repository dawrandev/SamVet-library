<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores documents attached inline into a TinyMCE rich-text editor (news/page
 * body) — a downloadable attachment (e.g. "qabul tartibi.pdf"), distinct from
 * EditorImageService's inline images. Public disk: this is admin-authored
 * public content, not a protected catalog resource.
 *
 * Content-addressed by SHA-256 (same reasoning as EditorImageService — the
 * same file often gets attached across the uz/ru/kk editor instances), but
 * the original filename is kept alongside it for a sensible download name.
 */
class EditorFileService
{
    public function store(UploadedFile $file): array
    {
        $hash = hash_file('sha256', $file->path());
        $path = 'editor-files/'.$hash.'.'.$file->extension();

        if (! Storage::disk('public')->exists($path)) {
            $file->storeAs('editor-files', basename($path), 'public');
        }

        return [
            'url' => Storage::disk('public')->url($path),
            'name' => $file->getClientOriginalName(),
        ];
    }
}
