<?php

namespace App\Models;

use App\Enums\ArticleCategory;
use App\Observers\ArticleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ArticleObserver::class])]
class Article extends Model
{
    use HasFactory;

    // Note: `slug` (set by the observer) and `views_count` (DB default) are
    // intentionally NOT fillable — only user-supplied fields belong here.
    protected $fillable = [
        'journal_issue_id', 'title', 'author',
        'resource_field_id', 'language_id', 'category',
        'doi', 'pages', 'annotation',
        'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
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

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
