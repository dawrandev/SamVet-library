<?php

namespace App\Services\Site;

use App\Repositories\Contracts\DissertationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the public dissertation catalog + detail pages.
 */
class DissertationPageService
{
    public function __construct(
        private readonly DissertationRepositoryInterface $dissertations,
    ) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function index(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->dissertations->paginate($filters, $perPage);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NotFoundHttpException when no dissertation matches the slug
     */
    public function show(string $slug): array
    {
        $dissertation = $this->dissertations->findBySlug($slug);

        if ($dissertation === null) {
            throw new NotFoundHttpException();
        }

        $this->dissertations->incrementViews($dissertation);

        return [
            'dissertation' => $dissertation,
            'hasOnline' => filled($dissertation->electronic_file),
        ];
    }
}
