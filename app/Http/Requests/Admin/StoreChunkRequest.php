<?php

namespace App\Http\Requests\Admin;

use App\Services\ChunkedUploadService;
use Illuminate\Foundation\Http\FormRequest;

class StoreChunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under 'auth' middleware. If roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'index' => ['required', 'integer', 'min:0'],
            // A single chunk, not the whole file — capped generously above the
            // configured chunk size so a slightly-oversized last chunk isn't rejected.
            'file' => ['required', 'file', 'max:'.(ChunkedUploadService::CHUNK_SIZE / 1024 * 2)],
        ];
    }
}
