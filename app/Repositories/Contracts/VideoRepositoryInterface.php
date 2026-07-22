<?php

namespace App\Repositories\Contracts;

use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Video is small enough (no drafts, no admin-only fields) that one
 * repository serves both the admin CRUD and the public site — same
 * reasoning as AudiobookRepositoryInterface.
 */
interface VideoRepositoryInterface
{
    /**
     * Filtered, paginated list of videos (name/author search).
     *
     * @param  array{search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Video;

    public function findBySlug(string $slug): ?Video;

    public function create(array $data): Video;

    public function update(Video $video, array $data): Video;

    public function delete(Video $video): void;

    /** Register one detail-page view. */
    public function incrementViews(Video $video): void;

    /**
     * A handful of other videos to surface as "similar" on the detail page.
     *
     * @return Collection<int, Video>
     */
    public function similar(Video $video, int $limit): Collection;
}
