<?php

use App\Enums\BookFormat;
use App\Enums\CatalogSort;
use App\Enums\CopyStatus;
use App\Enums\JournalPeriodicity;
use App\Enums\MenuItemType;
use App\Enums\PublicationKind;

$labelled = [
    BookFormat::class,
    CopyStatus::class,
    JournalPeriodicity::class,
    MenuItemType::class,
    PublicationKind::class,
    CatalogSort::class,
];

it('gives every case a non-empty label', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with($labelled);

it('has unique backing values', function (string $enum) {
    $values = array_map(fn ($c) => $c->value, $enum::cases());
    expect($values)->toEqual(array_unique($values));
})->with($labelled);

it('maps publication kinds to journal and newspaper', function () {
    expect(PublicationKind::Journal->value)->toBe('journal')
        ->and(PublicationKind::Newspaper->value)->toBe('newspaper');
});
