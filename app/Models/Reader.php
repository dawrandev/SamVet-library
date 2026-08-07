<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\LoanStatus;
use App\Enums\ReaderStatus;
use App\Observers\ReaderObserver;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ReaderObserver::class])]
class Reader extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasFactory;

    /** Password is never mass-assigned — the observer/admin sets it explicitly. */
    protected $hidden = ['password'];

    protected $fillable = [
        'id_number', 'registration_number', 'issued_date',
        'reader_type_id', 'full_name',
        'affiliation_place_id', 'affiliation_unit_id', 'affiliation_group_id',
        'nationality', 'birth_date', 'passport', 'pinfl', 'gender',
        'region_id', 'district_id', 'address', 'phone', 'member_year',
        'photo', 'other_library_member', 'note',
        'status', 'blocked_until', 'block_reason', 'left_reason', 'left_at',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => ReaderStatus::class,
            'gender' => Gender::class,
            'issued_date' => 'date',
            'birth_date' => 'date',
            'blocked_until' => 'date',
            'left_at' => 'datetime',
            'member_year' => 'integer',
        ];
    }

    // --- Relationships ---

    public function type(): BelongsTo
    {
        // Explicit FK — belongsTo() would otherwise infer "type_id" from the
        // relation method name, not the real "reader_type_id" column.
        return $this->belongsTo(ReaderType::class, 'reader_type_id');
    }

    public function affiliationPlace(): BelongsTo
    {
        return $this->belongsTo(AffiliationPlace::class);
    }

    public function affiliationUnit(): BelongsTo
    {
        return $this->belongsTo(AffiliationUnit::class);
    }

    public function affiliationGroup(): BelongsTo
    {
        return $this->belongsTo(AffiliationGroup::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->latest('issued_at');
    }

    /** Online reads/watches/listens (materials opened on the client site) — newest first. */
    public function onlineReads(): HasMany
    {
        return $this->hasMany(OnlineRead::class)->latest('read_at');
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(ReaderWarning::class)->latest('warned_at');
    }

    /** Events this reader has participated in — read-only here; managed from the Tadbirlar module. */
    public function eventParticipations(): HasMany
    {
        return $this->hasMany(EventParticipant::class)->with('event.locations');
    }

    public function computerSessions(): HasMany
    {
        return $this->hasMany(ComputerSession::class)->latest('issued_at');
    }

    // --- Helpers ---

    /** Books currently held (not yet returned). */
    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class)->where('status', LoanStatus::OnLoan->value);
    }

    /**
     * Group + specialty for a student, or job position for staff — the line
     * shown under a reader's name on the public "most active readers" wall.
     */
    public function affiliationLine(): ?string
    {
        if ($this->type?->is_student) {
            return collect([$this->affiliationGroup?->name, $this->affiliationUnit?->name])
                ->filter()
                ->implode(' · ') ?: null;
        }

        return $this->affiliationGroup?->name;
    }

    /** Two-letter initials — the fallback avatar when no photo is set. */
    public function initials(): string
    {
        return collect(explode(' ', trim($this->full_name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function isBlocked(): bool
    {
        return $this->status === ReaderStatus::Blocked
            || ($this->blocked_until !== null && $this->blocked_until->isFuture());
    }

    /** Has unreturned books — membership can't be finished while this is true. */
    public function hasOutstandingLoans(): bool
    {
        return $this->activeLoans()->exists();
    }

    /**
     * Only an active and non-blocked user can borrow a book.
     * Blocked / temporarily restricted / left — cannot borrow.
     */
    public function canBorrow(): bool
    {
        return $this->status === ReaderStatus::Active && ! $this->isBlocked();
    }

    /**
     * Only an active, non-blocked reader may sign in and read materials online.
     */
    public function canSignIn(): bool
    {
        return $this->status === ReaderStatus::Active && ! $this->isBlocked();
    }

    /**
     * "Remember me" is not offered to readers, so there is no token column.
     * Returning null makes the Authenticatable trait skip the token entirely.
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }
}
