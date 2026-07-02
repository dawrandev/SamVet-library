<?php

namespace App\Repositories\Eloquent;

use App\Models\ReaderEvent;
use App\Repositories\Contracts\EventRepositoryInterface;

class EventRepository implements EventRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReaderEvent
    {
        return ReaderEvent::create($data);
    }

    public function delete(ReaderEvent $event): void
    {
        $event->delete();
    }
}
