<?php

namespace App\Services;

use App\Data\PageData;
use App\Models\MenuItem;
use App\Models\Page;
use App\Repositories\Contracts\PageRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pages,
    ) {}

    /**
     * The page linked to the menu item (or null).
     */
    public function forMenuItem(MenuItem $menuItem): ?Page
    {
        return $this->pages->findForMenuItem($menuItem);
    }

    public function save(MenuItem $menuItem, PageData $data): Page
    {
        return DB::transaction(function () use ($menuItem, $data) {
            $existing = $this->pages->findForMenuItem($menuItem);

            $attributes = [
                'body' => $this->cleanBody($data->body),
            ];

            if ($data->cover) {
                $this->deleteFile($existing?->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover, 'pages/covers');
            }

            return $this->pages->updateOrCreateForMenuItem($menuItem, $attributes);
        });
    }

    /**
     * Sanitize the rich HTML for each language with Purifier (XSS protection).
     *
     * @param  array<string, string>  $body
     * @return array<string, string>
     */
    private function cleanBody(array $body): array
    {
        return array_map(
            static fn (string $html): string => Purifier::clean($html),
            $body,
        );
    }

    private function storePublic(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'public');
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
