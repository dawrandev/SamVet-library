<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'reader_id', 'book_copy_id', 'issued_at', 'due_at', 'returned_at', 'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function copy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    /** Whether it is overdue (on loan, but past the due date). */
    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::OnLoan
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
