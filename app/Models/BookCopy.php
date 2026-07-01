<?php

namespace App\Models;

use App\Enums\BookFormat;
use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'inventory_number', 'format', 'condition', 'status',
        'location_id', 'price', 'acquisition_act', 'disposal_act',
    ];

    protected function casts(): array
    {
        return [
            'format' => BookFormat::class,
            'condition' => CopyCondition::class,
            'status' => CopyStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
