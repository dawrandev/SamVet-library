<?php

namespace App\Services;

use App\Models\Reader;
use App\Models\ReaderEvent;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {}

    /**
     * Add an attended event/competition to a reader.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Reader $reader, array $data): ReaderEvent
    {
        return $this->events->create([
            'reader_id' => $reader->id,
            'date' => $data['date'],
            'name' => $data['name'],
            'place' => $data['place'] ?? null,
            'type' => $data['type'],
            'role' => $data['role'],
            'link' => $data['link'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
    }

    public function delete(ReaderEvent $event): void
    {
        $this->events->delete($event);
    }
}
