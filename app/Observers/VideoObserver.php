<?php

namespace App\Observers;

use App\Models\Video;
use Illuminate\Support\Str;

class VideoObserver
{
    /**
     * Slug is generated automatically (from the name) and guaranteed to be unique.
     */
    public function creating(Video $video): void
    {
        if (empty($video->slug)) {
            $video->slug = $this->uniqueSlug($video->name);
        }
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
