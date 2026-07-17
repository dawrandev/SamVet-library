<?php

namespace App\Repositories\Eloquent;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventRepository implements EventRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Event::query()
            ->with(['locations', 'news', 'participants.reader'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($filters['type'] ?? null, function ($query, string $type) {
                $query->where('type', $type);
            })
            ->latest('date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Event
    {
        return Event::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event;
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }
}
