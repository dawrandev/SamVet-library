<?php

namespace App\Services;

use App\Data\PageData;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageImage;
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
                'title' => $data->title,
                'body' => $this->cleanBody($data->body),
            ];

            if ($data->cover) {
                $this->deleteFile($existing?->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover, 'pages/covers');
            } elseif ($data->remove_cover && $existing?->cover_image) {
                $this->deleteFile($existing->cover_image);
                $attributes['cover_image'] = null;
            }

            $page = $this->pages->updateOrCreateForMenuItem($menuItem, $attributes);

            $this->removeGalleryImages($page, $data->remove_gallery_ids);
            $this->storeGallery($page, $data->gallery);

            return $page;
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

    /**
     * Deletes the chosen gallery images (file + row) — scoped to this page,
     * so a client-submitted id belonging to another page is never touched.
     *
     * @param  int[]  $ids
     */
    private function removeGalleryImages(Page $page, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $images = $page->images()->whereIn('id', $ids)->get();

        foreach ($images as $image) {
            $this->deleteFile($image->path);
            $image->delete();
        }
    }

    /**
     * Saves the gallery images (continuing the existing sort order).
     *
     * @param  array<int, UploadedFile>  $files
     */
    private function storeGallery(Page $page, array $files): void
    {
        if ($files === []) {
            return;
        }

        $sort = (int) $page->images()->max('sort_order');

        foreach ($files as $file) {
            $sort++;
            PageImage::create([
                'page_id' => $page->id,
                'path' => $this->storePublic($file, 'pages/gallery'),
                'sort_order' => $sort,
            ]);
        }
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
