<?php

namespace App\Repositories\Eloquent;

use App\Enums\LoanMaterialType;
use App\Enums\LoanStatus;
use App\Enums\PublicationKind;
use App\Models\BookCopy;
use App\Models\JournalCopy;
use App\Models\Loan;
use App\Repositories\Contracts\LoanRepositoryInterface;
use App\Support\SearchNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LoanRepository implements LoanRepositoryInterface
{
    public function create(array $data): Loan
    {
        return Loan::create($data);
    }

    public function update(Loan $loan, array $data): Loan
    {
        $loan->update($data);

        return $loan;
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'overdue';
        $search = trim((string) ($filters['search'] ?? ''));
        $today = now()->startOfDay();

        $query = Loan::query()->with($this->eagerLoanable());

        match ($scope) {
            'due_soon' => $query
                ->where('status', LoanStatus::OnLoan->value)
                ->whereBetween('due_at', [$today, $today->copy()->addDays(3)->endOfDay()])
                ->orderBy('due_at'),
            'active' => $query
                ->where('status', LoanStatus::OnLoan->value)
                ->orderBy('due_at'),
            'returned' => $query
                ->where('status', LoanStatus::Returned->value)
                ->latest('returned_at'),
            'all' => $query->latest('issued_at'),
            default => $query // overdue
                ->where('status', LoanStatus::OnLoan->value)
                ->where('due_at', '<', $today)
                ->orderBy('due_at'),
        };

        $this->applyMaterialType($query, $filters['material_type'] ?? null);

        if ($search !== '') {
            $this->applySearch($query, $search, includeReader: true);
        }

        if (! empty($filters['returned_from'])) {
            $query->where('returned_at', '>=', $filters['returned_from']);
        }

        if (! empty($filters['returned_to'])) {
            $query->where('returned_at', '<=', $filters['returned_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateForReader(int $readerId, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $query = Loan::query()
            ->with($this->eagerLoanable())
            ->where('reader_id', $readerId)
            ->latest('issued_at');

        $this->applyMaterialType($query, $filters['material_type'] ?? null);

        if ($search !== '') {
            $this->applySearch($query, $search, includeReader: false);
        }

        return $query->paginate($perPage, ['*'], 'materials_page')->withQueryString();
    }

    public function overdueCount(): int
    {
        return Loan::query()
            ->where('status', LoanStatus::OnLoan->value)
            ->where('due_at', '<', now()->startOfDay())
            ->count();
    }

    /**
     * Eager-load the loanable copy with the right chain per type, in one query set.
     *
     * @return array<string, mixed>
     */
    private function eagerLoanable(): array
    {
        return [
            'reader',
            'loanable' => function ($morphTo) {
                $morphTo->morphWith([
                    BookCopy::class => ['book'],
                    JournalCopy::class => ['issue.journal'],
                ]);
            },
        ];
    }

    private function applyMaterialType(Builder $query, ?string $type): void
    {
        match ($type) {
            LoanMaterialType::Book->value => $query->where('loanable_type', 'book_copy'),
            LoanMaterialType::Journal->value => $query->where('loanable_type', 'journal_copy')
                ->whereHasMorph('loanable', [JournalCopy::class], fn (Builder $q) => $q->whereHas(
                    'issue.journal', fn (Builder $j) => $j->where('kind', PublicationKind::Journal->value)
                )),
            LoanMaterialType::Newspaper->value => $query->where('loanable_type', 'journal_copy')
                ->whereHasMorph('loanable', [JournalCopy::class], fn (Builder $q) => $q->whereHas(
                    'issue.journal', fn (Builder $j) => $j->where('kind', PublicationKind::Newspaper->value)
                )),
            default => null,
        };
    }

    /**
     * One box searching four different things: the copy's inventory number,
     * the borrowed book's or journal's title, and (on the loans index, though
     * not on a reader's own page) the borrower's name.
     *
     * The three text fields go through the normalized columns, so a librarian
     * gets the same hit whether they type Latin or Cyrillic. The inventory
     * number stays a literal match — it's a code, not prose, and folding it
     * would only blur digits and letters that are meant to be exact.
     */
    private function applySearch(Builder $query, string $search, bool $includeReader): void
    {
        $normalized = SearchNormalizer::normalize($search);
        $rawTerm = addcslashes($search, '%_\\');

        $morphSearch = function (Builder $cq, string $type) use ($rawTerm, $normalized) {
            $cq->where('inventory_number', 'like', "%{$rawTerm}%");

            if ($normalized === '') {
                return;
            }

            $relation = $type === BookCopy::class ? 'book' : 'issue.journal';

            $cq->orWhereHas($relation, fn (Builder $r) => $r->where('search_text', 'like', "%{$normalized}%"));
        };

        $query->where(function (Builder $q) use ($normalized, $includeReader, $morphSearch) {
            if ($includeReader && $normalized !== '') {
                $q->whereHas('reader', fn (Builder $r) => $r->where('search_text', 'like', "%{$normalized}%"))
                    ->orWhereHasMorph('loanable', [BookCopy::class, JournalCopy::class], $morphSearch);
            } else {
                $q->whereHasMorph('loanable', [BookCopy::class, JournalCopy::class], $morphSearch);
            }
        });
    }
}
