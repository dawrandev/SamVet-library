<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StartChunkedUploadRequest;
use App\Http\Requests\Admin\StoreChunkRequest;
use App\Services\ChunkedUploadService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ChunkedUploadController extends Controller
{
    public function __construct(
        private readonly ChunkedUploadService $chunkedUploads,
    ) {}

    /**
     * Starts a new chunked-upload session for a large file field
     * (electronic_file, video_file, ...) — generic, not tied to any entity.
     */
    public function start(StartChunkedUploadRequest $request): JsonResponse
    {
        $session = $this->chunkedUploads->start(
            $request->string('filename')->toString(),
            $request->integer('total_size'),
            $request->kind(),
            $request->user(),
        );

        return response()->json([
            'token' => $session->token,
            'chunk_size' => ChunkedUploadService::CHUNK_SIZE,
            'total_chunks' => $session->total_chunks,
        ]);
    }

    /**
     * Receives one chunk. On the final chunk, also assembles + re-validates
     * the whole file — the response's "status" tells the client whether more
     * chunks are expected or the file is ready to be claimed by its token.
     */
    public function chunk(StoreChunkRequest $request, string $token): JsonResponse
    {
        $session = $this->chunkedUploads->findOwnedSession($token, $request->user()->id);

        if (! $session) {
            throw new NotFoundHttpException();
        }

        try {
            $session = $this->chunkedUploads->storeChunk($session, $request->integer('index'), $request->file('file'));

            if ($session->next_chunk_index === $session->total_chunks) {
                $session = $this->chunkedUploads->finish($session);
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'status' => $session->status,
            'next' => $session->next_chunk_index,
        ]);
    }
}
