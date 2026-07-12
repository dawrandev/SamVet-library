<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEditorImageRequest;
use App\Services\EditorImageService;
use Illuminate\Http\JsonResponse;

class EditorImageController extends Controller
{
    public function __construct(
        private readonly EditorImageService $editorImages,
    ) {}

    /**
     * Uploads one image dropped/pasted/inserted into a TinyMCE editor.
     * TinyMCE's upload handler expects `{ location: <url> }` back.
     */
    public function store(StoreEditorImageRequest $request): JsonResponse
    {
        $url = $this->editorImages->store($request->file('file'));

        return response()->json(['location' => $url]);
    }
}
