<?php

namespace App\Models;

use App\Observers\DissertationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([DissertationObserver::class])]
class Dissertation extends Model
{
    use HasFactory;

    // `slug` (set by the observer) and `views_count` (DB default) are intentionally
    // NOT fillable — only user-supplied fields belong here.
    protected $fillable = [
        'journal_issue_id', 'title', 'author',
        'resource_field_id', 'annotation',
        'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
        ];
    }

    // --- Relationships ---

    public function journalIssue(): BelongsTo
    {
        return $this->belongsTo(JournalIssue::class);
    }

    public function resourceField(): BelongsTo
    {
        return $this->belongsTo(ResourceField::class);
    }
}
