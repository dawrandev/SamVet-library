<?php

namespace App\Services;

use App\Data\EventData;
use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->events->paginate($filters, $perPage);
    }

    public function create(EventData $data): Event
    {
        return DB::transaction(function () use ($data) {
            $event = $this->events->create($data->toAttributes());
            $event->locations()->sync($data->location_ids);
            $this->syncParticipants($event, $data->participants);

            return $event;
        });
    }

    public function update(Event $event, EventData $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $event = $this->events->update($event, $data->toAttributes());
            $event->locations()->sync($data->location_ids);
            $this->syncParticipants($event, $data->participants);

            return $event;
        });
    }

    public function delete(Event $event): void
    {
        DB::transaction(fn () => $this->events->delete($event));
    }

    /**
     * Replaces the previous participant set — deletes then recreates,
     * mirroring ContributorService::sync().
     *
     * @param  array<int, array{is_external: mixed, reader_id: mixed, external_name: mixed, role: mixed}>  $rows
     */
    private function syncParticipants(Event $event, array $rows): void
    {
        $event->participants()->delete();

        foreach ($rows as $row) {
            $isExternal = filter_var($row['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $readerId = $isExternal ? null : ($row['reader_id'] ?? null);
            $externalName = $isExternal ? trim((string) ($row['external_name'] ?? '')) : null;
            $role = $row['role'] ?? null;

            if (! $role) {
                continue;
            }
            if (! $isExternal && ! $readerId) {
                continue;
            }
            if ($isExternal && $externalName === '') {
                continue;
            }

            $event->participants()->create([
                'reader_id' => $readerId,
                'external_name' => $externalName,
                'role' => $role,
            ]);
        }
    }
}
