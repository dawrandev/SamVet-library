<?php

namespace App\Services;

use App\Data\NewsData;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsImage;
use App\Repositories\Contracts\NewsRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class NewsService
{
    public function __construct(
        private readonly NewsRepositoryInterface $news,
    ) {}

    /**
     * Paginated, filtered list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->news->paginate($filters, $perPage);
    }

    /**
     * For the filter dropdowns on the list page.
     *
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'categories' => NewsCategory::orderBy('id')->get(),
        ];
    }

    /**
     * Options for the create/edit form.
     *
     * @return array<string, mixed>
     */
    public function formOptions(): array
    {
        return [
            'categories' => NewsCategory::orderBy('id')->get(),
        ];
    }

    public function create(NewsData $data): News
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toAttributes();
            $attributes['body'] = $this->cleanBody($data->body);

            if ($data->cover) {
                $attributes['cover_image'] = $this->storePublic($data->cover, 'news/covers');
            }

            $news = $this->news->create($attributes); // slug — Observer

            $this->storeGallery($news, $data->gallery);

            return $news;
        });
    }

    public function update(News $news, NewsData $data): News
    {
        return DB::transaction(function () use ($news, $data) {
            $attributes = $data->toAttributes();
            $attributes['body'] = $this->cleanBody($data->body);

            if ($data->cover) {
                $this->deleteFile($news->cover_image);
                $attributes['cover_image'] = $this->storePublic($data->cover, 'news/covers');
            } elseif ($data->remove_cover && $news->cover_image) {
                $this->deleteFile($news->cover_image);
                $attributes['cover_image'] = null;
            }

            $news = $this->news->update($news, $attributes);

            $this->removeGalleryImages($news, $data->remove_gallery_ids);
            $this->storeGallery($news, $data->gallery);

            return $news;
        });
    }

    public function delete(News $news): void
    {
        DB::transaction(function () use ($news) {
            $this->deleteFile($news->cover_image);

            foreach ($news->images as $image) {
                $this->deleteFile($image->path);
            }

            // Gallery records are deleted via FK cascade.
            $this->news->delete($news);
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
     * Deletes the chosen gallery images (file + row) — scoped to this news item,
     * so a client-submitted id belonging to another news item is never touched.
     *
     * @param  int[]  $ids
     */
    private function removeGalleryImages(News $news, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $images = $news->images()->whereIn('id', $ids)->get();

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
    private function storeGallery(News $news, array $files): void
    {
        if ($files === []) {
            return;
        }

        $sort = (int) $news->images()->max('sort_order');

        foreach ($files as $file) {
            $sort++;
            NewsImage::create([
                'news_id' => $news->id,
                'path' => $this->storePublic($file, 'news/gallery'),
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
