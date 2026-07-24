<?php

namespace App\Models;

use App\Enums\CopyCondition;
use App\Enums\LoanMaterialType;
use App\Enums\LoanStatus;
use App\Enums\PublicationKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reader_id', 'loanable_type', 'loanable_id', 'issued_at', 'due_at', 'returned_at',
        'status', 'note', 'issued_condition', 'returned_condition',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'issued_condition' => CopyCondition::class,
            'returned_condition' => CopyCondition::class,
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    /** The borrowed copy — a BookCopy or a JournalCopy. */
    public function loanable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Whether it is overdue (on loan, but past the due date). */
    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::OnLoan
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    /** Kitob / Gazeta / Jurnal — derived from the loanable copy. */
    public function materialType(): LoanMaterialType
    {
        if ($this->loanable instanceof BookCopy) {
            return LoanMaterialType::Book;
        }

        $kind = $this->loanable?->issue?->journal?->kind;

        return $kind === PublicationKind::Newspaper ? LoanMaterialType::Newspaper : LoanMaterialType::Journal;
    }

    /** Book title, or journal/newspaper name. */
    public function materialTitle(): string
    {
        if ($this->loanable instanceof BookCopy) {
            return $this->loanable->book?->title ?? '—';
        }

        return $this->loanable?->issue?->journal?->name ?? '—';
    }

    /** Author(s) for a book, or the issue's "YYYY, N-son" for a periodical. */
    public function materialSubtitle(): ?string
    {
        if ($this->loanable instanceof BookCopy) {
            $authors = $this->loanable->book?->authors;

            return filled($authors) ? $authors : null;
        }

        $issue = $this->loanable?->issue;

        if ($issue === null) {
            return null;
        }

        $parts = array_filter([
            $issue->year,
            $issue->issue_number ? __(':n-son', ['n' => $issue->issue_number]) : null,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function inventoryNumber(): ?string
    {
        return $this->loanable?->inventory_number;
    }
}
