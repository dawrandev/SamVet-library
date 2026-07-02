<?php

namespace App\Repositories\Eloquent;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Repositories\Contracts\LoanRepositoryInterface;
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

        $query = Loan::query()
            ->with(['reader', 'copy.book'])
            ->where('status', LoanStatus::OnLoan->value);

        match ($scope) {
            'due_soon' => $query
                ->whereBetween('due_at', [$today, $today->copy()->addDays(3)->endOfDay()])
                ->orderBy('due_at'),
            'active' => $query->orderBy('due_at'),
            default => $query // overdue
                ->where('due_at', '<', $today)
                ->orderBy('due_at'),
        };

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('reader', fn (Builder $r) => $r->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('copy', fn (Builder $c) => $c->where('inventory_number', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function overdueCount(): int
    {
        return Loan::query()
            ->where('status', LoanStatus::OnLoan->value)
            ->where('due_at', '<', now()->startOfDay())
            ->count();
    }
}
