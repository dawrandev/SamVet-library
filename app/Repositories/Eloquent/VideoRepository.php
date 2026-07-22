<?php

namespace App\Repositories\Eloquent;

use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VideoRepository implements VideoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Video::query()
            ->withCount('tracks')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Video
    {
        return Video::with('tracks')->find($id);
    }

    public function findBySlug(string $slug): ?Video
    {
        return Video::with('tracks')->where('slug', $slug)->first();
    }

    public function create(array $data): Video
    {
        return Video::create($data);
    }

    public function update(Video $video, array $data): Video
    {
        $video->update($data);

        return $video;
    }

    public function delete(Video $video): void
    {
        $video->delete();
    }

    public function incrementViews(Video $video): void
    {
        $video->increment('views_count');
    }

    public function similar(Video $video, int $limit): Collection
    {
        return Video::query()
            ->withCount('tracks')
            ->where('id', '!=', $video->id)
            ->latest('id')
            ->take($limit)
            ->get();
    }
}
