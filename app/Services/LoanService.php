<?php

namespace App\Services;

use App\Enums\CopyStatus;
use App\Enums\LoanStatus;
use App\Enums\ReaderStatus;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reader;
use App\Repositories\Contracts\LoanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanService
{
    /** Cache of the overdue books count (navbar/sidebar notification). */
    public const OVERDUE_CACHE_KEY = 'overdue_loans_count';

    public function __construct(
        private readonly LoanRepositoryInterface $loans,
    ) {}

    /**
     * List of issued books (overdue / due soon / active).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->loans->paginate($filters);
    }

    /**
     * Count of overdue books (for the notification).
     */
    public function overdueCount(): int
    {
        return $this->loans->overdueCount();
    }

    /**
     * Issue a book copy to a reader by inventory number.
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

        $copy = BookCopy::where('inventory_number', $inventoryNumber)->first();

        if ($copy === null) {
            throw new RuntimeException(__('Bunday inventar raqamli nusxa yo‘q.'));
        }

        if ($copy->status !== CopyStatus::Available) {
            throw new RuntimeException(__('Nusxa mavjud emas (berilgan/yo‘qotilgan).'));
        }

        $loan = DB::transaction(function () use ($reader, $copy, $dueAt, $note) {
            $loan = $this->loans->create([
                'reader_id' => $reader->id,
                'book_copy_id' => $copy->id,
                'issued_at' => now(),
                'due_at' => $dueAt,
                'status' => LoanStatus::OnLoan,
                'note' => $note,
            ]);

            $copy->update(['status' => CopyStatus::Borrowed]);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Return an issued book. The copy becomes "available" again.
     */
    public function returnLoan(Loan $loan): Loan
    {
        // If it has already been returned — do nothing.
        if ($loan->status !== LoanStatus::OnLoan) {
            return $loan;
        }

        $loan = DB::transaction(function () use ($loan) {
            $this->loans->update($loan, [
                'returned_at' => now(),
                'status' => LoanStatus::Returned,
            ]);

            $loan->copy?->update(['status' => CopyStatus::Available]);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Mark the copy as lost (when an issued book is not returned).
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

            $loan->copy?->update(['status' => CopyStatus::Lost]);

            return $loan;
        });

        $this->forgetOverdueCache();

        return $loan;
    }

    /**
     * Invalidates the overdue books cache — so the navbar/sidebar notification
     * updates immediately after a circulation state change.
     */
    private function forgetOverdueCache(): void
    {
        Cache::forget(self::OVERDUE_CACHE_KEY);
    }
}
