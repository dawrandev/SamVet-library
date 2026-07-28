<?php

namespace App\Data;

use App\Enums\CatalogResourceType;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\Video;

/**
 * One row of the unified catalog result list — a normalized, render-ready
 * projection of a Book, Audiobook, Video, Dissertation or Avtoreferat.
 * Built in CatalogRepository, consumed by <x-site.catalog-card>.
 */
final class CatalogItem
{
    /**
     * @param  array<int, \App\Enums\BookFormat>  $formats  book copy formats (books only)
     */
    public function __construct(
        public readonly CatalogResourceType $type,
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $slug,
        public readonly ?string $author = null,
        public readonly ?string $coverImage = null,
        public readonly int $viewsCount = 0,
        public readonly ?int $year = null,
        public readonly ?string $typeLabel = null,
        public readonly array $formats = [],
        public readonly ?int $availableCopies = null,
        public readonly ?int $trackCount = null,
        public readonly bool $hasFile = false,
    ) {}

    /** Public URL of the underlying resource. */
    public function url(): string
    {
        return route($this->type->routeName(), $this->slug);
    }

    /** Corner badge: a book shows its own BookType, everything else its own kind. */
    public function badgeLabel(): string
    {
        return $this->typeLabel ?: $this->type->label();
    }

    public static function fromBook(Book $book): self
    {
        return new self(
            type: CatalogResourceType::Book,
            id: $book->id,
            title: (string) $book->title,
            slug: $book->slug,
            author: $book->authors,
            coverImage: $book->cover_image,
            viewsCount: (int) $book->views_count,
            year: $book->publication_year,
            typeLabel: $book->type?->name,
            formats: $book->relationLoaded('copies')
                ? $book->copies->pluck('format')->filter()->unique()->values()->all()
                : [],
            availableCopies: $book->available_copies ?? null,
        );
    }

    public static function fromAudiobook(Audiobook $audiobook): self
    {
        return new self(
            type: CatalogResourceType::Audiobook,
            id: $audiobook->id,
            title: (string) $audiobook->name,
            slug: $audiobook->slug,
            author: $audiobook->author,
            coverImage: $audiobook->cover_image,
            viewsCount: (int) $audiobook->views_count,
            trackCount: $audiobook->tracks_count ?? null,
        );
    }

    public static function fromVideo(Video $video): self
    {
        return new self(
            type: CatalogResourceType::Video,
            id: $video->id,
            title: (string) $video->name,
            slug: $video->slug,
            author: $video->author,
            coverImage: $video->cover_image,
            viewsCount: (int) $video->views_count,
            trackCount: $video->tracks_count ?? null,
        );
    }

    public static function fromDissertation(Dissertation $dissertation): self
    {
        return new self(
            type: CatalogResourceType::Dissertation,
            id: $dissertation->id,
            title: (string) $dissertation->title,
            slug: $dissertation->slug,
            author: $dissertation->author,
            viewsCount: (int) $dissertation->views_count,
            year: $dissertation->defense_year,
            hasFile: filled($dissertation->electronic_file),
        );
    }

    public static function fromAvtoreferat(Avtoreferat $avtoreferat): self
    {
        return new self(
            type: CatalogResourceType::Avtoreferat,
            id: $avtoreferat->id,
            title: (string) $avtoreferat->title,
            slug: $avtoreferat->slug,
            author: $avtoreferat->author,
            viewsCount: (int) $avtoreferat->views_count,
            year: $avtoreferat->defense_year,
            hasFile: filled($avtoreferat->electronic_file),
        );
    }
}
