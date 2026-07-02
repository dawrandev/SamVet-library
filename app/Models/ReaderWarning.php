<?php

namespace App\Models;

use App\Enums\WarningReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReaderWarning extends Model
{
    protected $fillable = ['reader_id', 'reason', 'note', 'warned_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'reason' => WarningReason::class,
            'warned_at' => 'date',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
