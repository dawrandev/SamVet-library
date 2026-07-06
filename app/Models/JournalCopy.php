<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_issue_id', 'inventory_number', 'condition', 'status',
        'location_id', 'arrival_date',
    ];

    protected function casts(): array
    {
        return [
            'condition' => CopyCondition::class,
            'status' => CopyStatus::class,
            'arrival_date' => 'date',
        ];
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(JournalIssue::class, 'journal_issue_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
