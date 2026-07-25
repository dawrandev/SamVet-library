<?php

namespace App\Services\Site;

use App\Enums\ComputerLocation;
use App\Repositories\Contracts\ComputerRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Reader-facing view of the library's computers: which room they're in,
 * and whether each is free, occupied, broken, or under repair right now.
 */
class ComputerAvailabilityService
{
    public function __construct(
        private readonly ComputerRepositoryInterface $computers,
    ) {}

    /**
     * One entry per room that actually has computers, in the enum's fixed
     * order, each carrying its computers plus a free/total count.
     *
     * @return Collection<int, array{location: ComputerLocation, computers: Collection<int, \App\Models\Computer>, freeCount: int, totalCount: int}>
     */
    public function rooms(): Collection
    {
        $grouped = $this->computers->publicByLocation();

        return collect(ComputerLocation::cases())
            ->map(function (ComputerLocation $location) use ($grouped): array {
                $computers = $grouped->get($location->value, collect());

                return [
                    'location' => $location,
                    'computers' => $computers,
                    'freeCount' => $computers->filter(fn ($c) => $c->publicStatusColor() === 'success')->count(),
                    'totalCount' => $computers->count(),
                ];
            })
            ->filter(fn (array $room) => $room['totalCount'] > 0)
            ->values();
    }
}
