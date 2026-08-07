<?php

namespace App\Repositories\Eloquent;

use App\Models\OnlineRead;
use App\Models\Reader;
use App\Repositories\Contracts\OnlineReadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class OnlineReadRepository implements OnlineReadRepositoryInterface
{
    public function log(Reader $reader, Model $readable): OnlineRead
    {
        return OnlineRead::create([
            'reader_id' => $reader->id,
            'readable_type' => $readable->getMorphClass(),
            'readable_id' => $readable->getKey(),
            'read_at' => now(),
        ]);
    }

    public function paginateForReader(int $readerId, int $perPage = 10): LengthAwarePaginator
    {
        return OnlineRead::query()
            ->with('readable')
            ->where('reader_id', $readerId)
            ->latest('read_at')
            ->paginate($perPage, ['*'], 'readings_page')
            ->withQueryString();
    }
}
