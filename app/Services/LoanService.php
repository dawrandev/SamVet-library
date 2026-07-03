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
    /** Muddati o'tgan kitoblar soni keshi (navbar/sidebar bildirishnomasi). */
    public const OVERDUE_CACHE_KEY = 'overdue_loans_count';

    public function __construct(
        private readonly LoanRepositoryInterface $loans,
    ) {}

    /**
     * Berilgan kitoblar ro'yxati (muddati o'tgan / yaqin / faol).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->loans->paginate($filters);
    }

    /**
     * Muddati o'tgan kitoblar soni (bildirishnoma uchun).
     */
    public function overdueCount(): int
    {
        return $this->loans->overdueCount();
    }

    /**
     * Inventar raqami bo'yicha kitob nusxasini foydalanuvchiga berish.
     *
     * @throws RuntimeException  Bloklangan foydalanuvchi / nusxa topilmadi / band bo'lganda.
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
     * Berilgan kitobni qaytarish. Nusxa yana "mavjud" bo'ladi.
     */
    public function returnLoan(Loan $loan): Loan
    {
        // Allaqachon qaytarilgan bo'lsa — hech narsa qilmaymiz.
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
     * Nusxa yo'qolgan deb belgilash (berilgan kitob qaytmasa).
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
     * Muddati o'tgan kitoblar keshini bekor qiladi — oldi-berdi holati o'zgargach
     * navbar/sidebar bildirishnomasi darhol yangilanishi uchun.
     */
    private function forgetOverdueCache(): void
    {
        Cache::forget(self::OVERDUE_CACHE_KEY);
    }
}
