<?php

namespace App\Repositories\Eloquent;

use App\Models\Computer;
use App\Repositories\Contracts\ComputerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ComputerRepository implements ComputerRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Computer::query()
            // Search (model, inventory number)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('model', 'like', "%{$search}%")
                        ->orWhere('inventory_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, function ($query, string $type) {
                $query->where('type', $type);
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                $query->where('status', $status);
            })
            ->when($filters['location'] ?? null, function ($query, string $location) {
                $query->where('location', $location);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function publicByLocation(): Collection
    {
        return Computer::query()
            ->whereNotNull('computer_number')
            ->with(['sessions' => fn ($q) => $q->whereNull('returned_at')])
            ->orderBy('computer_number')
            ->get()
            ->groupBy(fn (Computer $computer) => $computer->location?->value);
    }

    public function find(int $id): ?Computer
    {
        return Computer::find($id);
    }

    public function create(array $data): Computer
    {
        return Computer::create($data);
    }

    public function update(Computer $computer, array $data): Computer
    {
        $computer->update($data);

        return $computer;
    }

    public function delete(Computer $computer): void
    {
        $computer->delete();
    }
}
