<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id', 'year', 'issue_date', 'issue_number', 'pages',
        'cover_image', 'electronic_file',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'issue_date' => 'date',
            'pages' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function copies(): HasMany
    {
        return $this->hasMany(JournalCopy::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
