<?php

namespace App\Enums;

/**
 * The public catalog's "Shakli" filter — one vocabulary spanning every
 * resource type, not just books.
 *
 * Print/Electronic/Braille keep BookFormat's own backing values (so old
 * bookmarked `?formats[]=print` links keep working) and still map onto a
 * book copy's own format. Audio/Video each pull in a whole resource type.
 * Dissertations and avtoreferats have no format of their own — they are
 * PDF-only, so they are permanently folded into "Elektron".
 */
enum CatalogFormat: string
{
    case Print = 'print';
    case Electronic = 'electronic';
    case Braille = 'braille';
    case Audio = 'audio';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Print => __('Bosma'),
            self::Electronic => __('Elektron'),
            self::Braille => __('Brayl'),
            self::Audio => __('Audio'),
            self::Video => __('Video'),
        };
    }

    /** The book copy format this option constrains Book results to, if any. */
    public function bookFormat(): ?BookFormat
    {
        return match ($this) {
            self::Print => BookFormat::Print,
            self::Electronic => BookFormat::Electronic,
            self::Braille => BookFormat::Braille,
            self::Audio, self::Video => null,
        };
    }

    /**
     * Which resource types this option can surface.
     *
     * @return array<int, CatalogResourceType>
     */
    public function resourceTypes(): array
    {
        return match ($this) {
            self::Print, self::Braille => [CatalogResourceType::Book],
            // Dissertations/avtoreferats are always electronic — they ride along here.
            self::Electronic => [
                CatalogResourceType::Book,
                CatalogResourceType::Dissertation,
                CatalogResourceType::Avtoreferat,
            ],
            self::Audio => [CatalogResourceType::Audiobook],
            self::Video => [CatalogResourceType::Video],
        };
    }

    /**
     * Union of resource types across a set of selected options.
     *
     * @param  array<int, self>  $formats
     * @return array<int, CatalogResourceType>
     */
    public static function resourceTypesFor(array $formats): array
    {
        return collect($formats)
            ->flatMap(fn (self $f) => $f->resourceTypes())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The book copy formats implied by a set of selected options. Empty
     * means "no book-copy constraint at all" (e.g. only Audio/Video chosen).
     *
     * @param  array<int, self>  $formats
     * @return array<int, string>  BookFormat backing values
     */
    public static function bookFormatValuesFor(array $formats): array
    {
        return collect($formats)
            ->map(fn (self $f) => $f->bookFormat()?->value)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
