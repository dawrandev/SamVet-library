<?php

namespace App\Repositories\Contracts;

use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoanRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Loan;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Loan $loan, array $data): Loan;

    /**
     * Berilgan kitoblar ro'yxati (filtr: scope, search).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Muddati o'tgan (on_loan + due_at < bugun) loanlar soni.
     */
    public function overdueCount(): int;
}
