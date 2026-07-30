<?php

namespace App\Models;

use App\Enums\ChunkedUploadKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadSession extends Model
{
    protected $fillable = [
        'token', 'admin_id', 'kind', 'original_filename',
        'total_size', 'total_chunks', 'next_chunk_index',
        'status', 'assembled_path',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ChunkedUploadKind::class,
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
