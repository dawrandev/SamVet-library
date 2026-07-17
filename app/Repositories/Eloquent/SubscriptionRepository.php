<?php

namespace App\Repositories\Eloquent;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->with(['reader', 'journal', 'deliveryLocation']) // eager load — no N+1
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function sumAmount(array $filters = []): float
    {
        return (float) $this->filtered($filters)->sum('amount');
    }

    public function find(int $id): ?Subscription
    {
        return Subscription::with(['reader', 'journal'])->find($id);
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);

        return $subscription;
    }

    public function delete(Subscription $subscription): void
    {
        $subscription->delete();
    }

    /**
     * Shared filter query builder (used by both paginate and sumAmount).
     *
     * @param  array<string, mixed>  $filters
     */
    private function filtered(array $filters): Builder
    {
        // Cast filter values to int here: request input is a string, and a
        // non-numeric value (e.g. ?year=abc) would otherwise fatal on a typed param.
        return Subscription::query()
            ->when($filters['reader_id'] ?? null, function (Builder $query, $value) {
                $query->where('reader_id', (int) $value);
            })
            ->when($filters['journal_id'] ?? null, function (Builder $query, $value) {
                $query->where('journal_id', (int) $value);
            })
            ->when($filters['year'] ?? null, function (Builder $query, $value) {
                $query->where('year', (int) $value);
            })
            ->when($filters['source'] ?? null, function (Builder $query, $value) {
                $query->where('source', $value);
            });
    }
}
