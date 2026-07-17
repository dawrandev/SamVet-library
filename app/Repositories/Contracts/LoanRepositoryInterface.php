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
     * List of loaned-out materials, across all readers (filters: scope, search, material_type).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * One reader's full loan history (filters: search, material_type), 10/page by default.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateForReader(int $readerId, array $filters, int $perPage = 10): LengthAwarePaginator;

    /**
     * Number of overdue loans (on_loan + due_at < today).
     */
    public function overdueCount(): int;
}
