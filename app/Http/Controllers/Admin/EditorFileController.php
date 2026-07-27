<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEditorFileRequest;
use App\Services\EditorFileService;
use Illuminate\Http\JsonResponse;

class EditorFileController extends Controller
{
    public function __construct(
        private readonly EditorFileService $editorFiles,
    ) {}

    /**
     * Uploads one document attached via the TinyMCE editor's file button.
     */
    public function store(StoreEditorFileRequest $request): JsonResponse
    {
        return response()->json($this->editorFiles->store($request->file('file')));
    }
}
