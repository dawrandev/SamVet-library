<?php

namespace App\Observers;

use App\Models\Video;
use App\Services\SearchIndexService;
use Illuminate\Support\Str;

class VideoObserver
{
    public function __construct(
        private readonly SearchIndexService $searchIndex,
    ) {}

    /**
     * Slug is generated automatically (from the name) and guaranteed to be unique.
     */
    public function creating(Video $video): void
    {
        if (empty($video->slug)) {
            $video->slug = $this->uniqueSlug($video->name);
        }
    }

    /**
     * Refresh the script-independent search columns on every write, so a
     * record stays findable in both Latin and Cyrillic the moment it's saved.
     */
    public function saving(Video $video): void
    {
        $this->searchIndex->fill($video);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Video::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
