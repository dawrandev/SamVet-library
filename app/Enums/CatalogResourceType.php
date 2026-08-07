<?php

namespace App\Enums;

use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

/** Which of the five catalogued resource types a merged catalog row is. */
enum CatalogResourceType: string
{
    case Book = 'book';
    case Audiobook = 'audiobook';
    case Video = 'video';
    case Dissertation = 'dissertation';
    case Avtoreferat = 'avtoreferat';

    public function label(): string
    {
        return match ($this) {
            self::Book => __('Kitob'),
            self::Audiobook => __('Audiokitob'),
            self::Video => __('Video'),
            self::Dissertation => __('Dissertatsiya'),
            self::Avtoreferat => __('Avtoreferat'),
        };
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Book => Book::class,
            self::Audiobook => Audiobook::class,
            self::Video => Video::class,
            self::Dissertation => Dissertation::class,
            self::Avtoreferat => Avtoreferat::class,
        };
    }

    /** Public "show" route name for this type — always takes a {slug}. */
    public function routeName(): string
    {
        return match ($this) {
            self::Book => 'book.show',
            self::Audiobook => 'audiobook.show',
            self::Video => 'video.show',
            self::Dissertation => 'dissertation.show',
            self::Avtoreferat => 'avtoreferat.show',
        };
    }

    /** The column holding this type's display title ("name" on media types). */
    public function titleColumn(): string
    {
        return match ($this) {
            self::Audiobook, self::Video => 'name',
            default => 'title',
        };
    }

    /** The column holding this type's free-text author ("authors", plural, on books). */
    public function authorColumn(): string
    {
        return $this === self::Book ? 'authors' : 'author';
    }

    /**
     * Only books carry the Turi/Til/Yil taxonomy — using one of those facets
     * narrows the whole catalog down to books by construction, not a gap to
     * fill in for the other four types.
     */
    public function supportsBookFacets(): bool
    {
        return $this === self::Book;
    }

    /**
     * Book, Dissertation and Avtoreferat each have their own `category_id` —
     * Audiobook/Video don't, so a category filter must exclude them (same
     * reasoning as supportsBookFacets(), just for the one facet the other
     * two catalogued types also carry).
     */
    public function supportsCategoryFacet(): bool
    {
        return in_array($this, [self::Book, self::Dissertation, self::Avtoreferat], true);
    }
}
