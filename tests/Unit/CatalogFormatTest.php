<?php

use App\Enums\BookFormat;
use App\Enums\CatalogFormat;
use App\Enums\CatalogResourceType;

it('gives every case a non-empty label', function () {
    foreach (CatalogFormat::cases() as $format) {
        expect($format->label())->toBeString()->not->toBeEmpty();
    }
});

it('keeps Print/Electronic/Braille backing values identical to BookFormat, so old bookmarked links keep working', function () {
    expect(CatalogFormat::Print->value)->toBe(BookFormat::Print->value)
        ->and(CatalogFormat::Electronic->value)->toBe(BookFormat::Electronic->value)
        ->and(CatalogFormat::Braille->value)->toBe(BookFormat::Braille->value);
});

it('maps bookFormat() correctly, null for Audio/Video', function () {
    expect(CatalogFormat::Print->bookFormat())->toBe(BookFormat::Print)
        ->and(CatalogFormat::Electronic->bookFormat())->toBe(BookFormat::Electronic)
        ->and(CatalogFormat::Braille->bookFormat())->toBe(BookFormat::Braille)
        ->and(CatalogFormat::Audio->bookFormat())->toBeNull()
        ->and(CatalogFormat::Video->bookFormat())->toBeNull();
});

it('maps each option to its resource types', function () {
    expect(CatalogFormat::Print->resourceTypes())->toBe([CatalogResourceType::Book])
        ->and(CatalogFormat::Braille->resourceTypes())->toBe([CatalogResourceType::Book])
        ->and(CatalogFormat::Electronic->resourceTypes())->toBe([
            CatalogResourceType::Book,
            CatalogResourceType::Dissertation,
            CatalogResourceType::Avtoreferat,
        ])
        ->and(CatalogFormat::Audio->resourceTypes())->toBe([CatalogResourceType::Audiobook])
        ->and(CatalogFormat::Video->resourceTypes())->toBe([CatalogResourceType::Video]);
});

it('unions resourceTypesFor() across multiple selected options without duplicates', function () {
    $types = CatalogFormat::resourceTypesFor([CatalogFormat::Electronic, CatalogFormat::Audio]);

    expect($types)->toEqualCanonicalizing([
        CatalogResourceType::Book,
        CatalogResourceType::Dissertation,
        CatalogResourceType::Avtoreferat,
        CatalogResourceType::Audiobook,
    ]);
});

it('resourceTypesFor() returns every type when nothing selected maps to it', function () {
    expect(CatalogFormat::resourceTypesFor([]))->toBe([]);
});

it('bookFormatValuesFor() collects only the book-copy-format-bearing options', function () {
    $values = CatalogFormat::bookFormatValuesFor([CatalogFormat::Print, CatalogFormat::Audio, CatalogFormat::Braille]);

    expect($values)->toEqualCanonicalizing([BookFormat::Print->value, BookFormat::Braille->value]);
});

it('bookFormatValuesFor() is empty when only Audio/Video is selected', function () {
    expect(CatalogFormat::bookFormatValuesFor([CatalogFormat::Audio, CatalogFormat::Video]))->toBe([]);
});
