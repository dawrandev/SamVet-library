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

    public function loans(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Loan::class, 'loanable');
    }

    /** The currently active (not returned) loan. */
    public function currentLoan(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Loan::class, 'loanable')->where('status', \App\Enums\LoanStatus::OnLoan->value);
    }
}
