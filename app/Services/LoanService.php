<?php

namespace App\Services;

use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReaderStatus;
use App\Models\BookCopy;
use App\Models\JournalCopy;
use App\Models\Loan;
use App\Models\Reader;
use App\Repositories\Contracts\LoanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanService
{
    /** Cache of the overdue materials count (navbar/sidebar notification). */
    public const OVERDUE_CACHE_KEY = 'overdue_loans_count';

    public function __construct(
        private readonly LoanRepositoryInterface $loans,
    ) {}

    /**
     * List of issued materials (overdue / due soon / active), across all readers.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->loans->paginate($filters);
    }

    /**
     * One reader's full loan history — paginated, searchable, filterable by material type.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForReader(int $readerId, array $filters): LengthAwarePaginator
    {
        return $this->loans->paginateForReader($readerId, $filters);
    }

    /**
     * Count of overdue materials (for the notification).
     */
    public function overdueCount(): int
    {
        return $this->loans->overdueCount();
    }

    /**
     * Issue a copy (book or journal/newspaper) to a reader by inventory number.
     *
     * @throws RuntimeException  When the reader is blocked / the copy is not found / it is unavailable.
     */
    public function issueByInventory(Reader $reader, string $inventoryNumber, string $dueAt, ?string $note): Loan
    {
        if (! $reader->canBorrow()) {
            throw new RuntimeException(match ($reader->status) {
                ReaderStatus::Left => __('Foydalanuvchi kutubxonadan chiqarilgan (tugatilgan).'),
                ReaderStatus::Suspended => __('Foydalanuvchi vaqtincha cheklangan.'),
                default => __('Foydalanuvchi bloklangan.'),
            });
        }

        [$loanableType, $copy] = $this->findCopyByInventory($inventoryNumber);

        if ($copy === null) {
            throw new RuntimeException(__('Bunday inventar raqamli nusxa yo‘q.'));
        }

        if ($copy->status !== CopyStatus::Available) {
            throw new RuntimeException(__('Nusxa mavjud emas (berilgan/yo‘qotilgan).'));
        }

        $loan = DB::transaction(function () use ($reader, $loanableType, $copy, $dueAt, $note) {
            $loan = $this->loans->create([
                'reader_id' => $reader->id,
                'loanable_type' => $loanableType,
                'loanable_id' => $copy->id,
                'issued_at' => now(),
                'due_at' => $dueAt,
                'status' => LoanStatus::OnLoan,
                'note' => $note,
                'issued_condition' => $copy->condition,
            ]);

            $copy->update(['status' => CopyStatus::Borrowed]);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Return an issued material. The copy becomes "available" again, and its
     * live condition is updated when the librarian records one on return.
     */
    public function returnLoan(Loan $loan, ?CopyCondition $returnedCondition = null): Loan
    {
        // If it has already been returned — do nothing.
        if ($loan->status !== LoanStatus::OnLoan) {
            return $loan;
        }

        $loan = DB::transaction(function () use ($loan, $returnedCondition) {
            $this->loans->update($loan, [
                'returned_at' => now(),
                'status' => LoanStatus::Returned,
                'returned_condition' => $returnedCondition,
            ]);

            $loan->loanable?->update(array_filter([
                'status' => CopyStatus::Available,
                'condition' => $returnedCondition,
            ], fn ($v) => $v !== null));

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Mark the copy as lost (when an issued material is not returned).
     */
    public function markLost(Loan $loan): Loan
    {
        if ($loan->status !== LoanStatus::OnLoan) {
            return $loan;
        }

        $loan = DB::transaction(function () use ($loan) {
            $this->loans->update($loan, [
                'status' => LoanStatus::Lost,
            ]);

            $loan->loanable?->update(['status' => CopyStatus::Lost]);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Looks up an available copy by inventory number — books first, then
     * journal/newspaper copies. Returns the morph-map alias alongside it.
     *
     * @return array{0: string, 1: BookCopy|JournalCopy|null}
     */
    private function findCopyByInventory(string $inventoryNumber): array
    {
        $bookCopy = BookCopy::where('inventory_number', $inventoryNumber)->first();

        if ($bookCopy !== null) {
            return ['book_copy', $bookCopy];
        }

        $journalCopy = JournalCopy::where('inventory_number', $inventoryNumber)->first();

        return ['journal_copy', $journalCopy];
    }

    /**
     * Invalidates the overdue materials cache — so the navbar/sidebar notification
     * updates immediately after a circulation state change.
     */
    private function forgetOverdueCache(): void
    {
        Cache::forget(self::OVERDUE_CACHE_KEY);
    }
}
