<?php

namespace App\Repositories\Eloquent;

use App\Models\Subscriber;
use App\Repositories\Contracts\SubscriberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubscriberRepository implements SubscriberRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Subscriber::query()
            // Search (full name, department)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->withCount('subscriptions')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(): Collection
    {
        return Subscriber::orderBy('full_name')->get();
    }

    public function find(int $id): ?Subscriber
    {
        return Subscriber::find($id);
    }

    public function create(array $data): Subscriber
    {
        return Subscriber::create($data);
    }

    public function update(Subscriber $subscriber, array $data): Subscriber
    {
        $subscriber->update($data);

        return $subscriber;
    }

    public function delete(Subscriber $subscriber): void
    {
        $subscriber->delete();
    }
}
