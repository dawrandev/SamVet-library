<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\LoanStatus;
use App\Enums\ReaderStatus;
use App\Enums\ReaderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reader extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_number', 'registration_number', 'issued_date',
        'type', 'full_name',
        'affiliation_place', 'affiliation_unit', 'affiliation_group',
        'nationality', 'birth_date', 'passport', 'pinfl', 'gender',
        'district', 'address', 'phone', 'member_year',
        'photo', 'other_library_member', 'note',
        'status', 'blocked_until', 'block_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReaderType::class,
            'status' => ReaderStatus::class,
            'gender' => Gender::class,
            'issued_date' => 'date',
            'birth_date' => 'date',
            'blocked_until' => 'date',
            'member_year' => 'integer',
        ];
    }

    // --- Relationships ---

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->latest('issued_at');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(ReaderWarning::class)->latest('warned_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReaderEvent::class)->latest('date');
    }

    public function computerSessions(): HasMany
    {
        return $this->hasMany(ComputerSession::class)->latest('date');
    }

    // --- Helpers ---

    /** Books currently held (not yet returned). */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->where('status', LoanStatus::OnLoan->value);
    }

    public function isBlocked(): bool
    {
        return $this->status === ReaderStatus::Blocked
            || ($this->blocked_until !== null && $this->blocked_until->isFuture());
    }

    /**
     * Only an active and non-blocked user can borrow a book.
     * Blocked / temporarily restricted / left — cannot borrow.
     */
    public function canBorrow(): bool
    {
        return $this->status === ReaderStatus::Active && ! $this->isBlocked();
    }
}
