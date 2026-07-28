<?php

use App\Data\CatalogFilters;
use App\Enums\CatalogFormat;
use App\Enums\CatalogSearchScope;
use App\Enums\CatalogSort;

it('is not active when no narrowing filter is set', function () {
    expect((new CatalogFilters())->isActive())->toBeFalse();
});

it('is active when any narrowing filter is set', function () {
    expect((new CatalogFilters(search: 'veterinariya'))->isActive())->toBeTrue();
    expect((new CatalogFilters(types: [1]))->isActive())->toBeTrue();
    expect((new CatalogFilters(languages: [2]))->isActive())->toBeTrue();
    expect((new CatalogFilters(formats: ['print']))->isActive())->toBeTrue();
    expect((new CatalogFilters(yearFrom: 2000))->isActive())->toBeTrue();
    expect((new CatalogFilters(author: 'Aliyev'))->isActive())->toBeTrue();
});

it('defaults to the newest sort', function () {
    expect((new CatalogFilters())->sort)->toBe(CatalogSort::Newest);
});

it('gives every sort option a non-empty label', function () {
    foreach (CatalogSort::cases() as $sort) {
        expect($sort->label())->toBeString()->not->toBeEmpty();
    }
});

it('resolves formatCases() and drops unknown values', function () {
    $filters = new CatalogFilters(formats: ['print', 'audio', 'not-a-format']);

    expect($filters->formatCases())->toBe([CatalogFormat::Print, CatalogFormat::Audio]);
});

it('is not booksOnly by default', function () {
    expect((new CatalogFilters())->booksOnly())->toBeFalse();
});

it('is booksOnly when a book-only facet is active', function () {
    expect((new CatalogFilters(categories: [1]))->booksOnly())->toBeTrue();
    expect((new CatalogFilters(types: [1]))->booksOnly())->toBeTrue();
    expect((new CatalogFilters(languages: [1]))->booksOnly())->toBeTrue();
    expect((new CatalogFilters(yearFrom: 2000))->booksOnly())->toBeTrue();
    expect((new CatalogFilters(yearTo: 2020))->booksOnly())->toBeTrue();
    expect((new CatalogFilters(scope: CatalogSearchScope::Isbn))->booksOnly())->toBeTrue();
});

it('is not booksOnly for a plain search, author, or Shakli-only filter', function () {
    expect((new CatalogFilters(search: 'veterinariya'))->booksOnly())->toBeFalse();
    expect((new CatalogFilters(author: 'Aliyev'))->booksOnly())->toBeFalse();
    expect((new CatalogFilters(formats: ['audio']))->booksOnly())->toBeFalse();
});
