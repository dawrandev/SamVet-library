<?php

namespace App\Services\Site;

use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public avtoreferat catalog + detail pages.
 */
class AvtoreferatPageService
{
    public function __construct(
        private readonly AvtoreferatRepositoryInterface $avtoreferats,
    ) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function index(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->avtoreferats->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no avtoreferat matches the slug
     */
    public function show(string $slug): array
    {
        $avtoreferat = $this->avtoreferats->findBySlug($slug);

        if ($avtoreferat === null) {
            throw new NotFoundHttpException();
        }

        $this->avtoreferats->incrementViews($avtoreferat);

        return [
            'avtoreferat' => $avtoreferat,
            'hasOnline' => filled($avtoreferat->electronic_file),
        ];
    }
}
