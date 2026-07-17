<?php

namespace Database\Factories;

use App\Enums\CopyCondition;
use App\Enums\LoanStatus;
use App\Models\BookCopy;
use App\Models\JournalCopy;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'reader_id' => Reader::factory(),
            'loanable_type' => 'book_copy',
            'loanable_id' => BookCopy::factory(),
            'issued_at' => now(),
            'due_at' => now()->addDays(15),
            'returned_at' => null,
            'status' => LoanStatus::OnLoan->value,
            'issued_condition' => CopyCondition::New->value,
        ];
    }

    /** A loan for a journal/newspaper copy instead of a book. */
    public function journalCopy(): static
    {
        return $this->state(fn () => [
            'loanable_type' => 'journal_copy',
            'loanable_id' => JournalCopy::factory(),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'returned_at' => now(),
            'status' => LoanStatus::Returned->value,
        ]);
    }
}
