<?php

use App\Data\CatalogFilters;
use App\Enums\CatalogSort;

it('is not active when no narrowing filter is set', function () {
    expect((new CatalogFilters())->isActive())->toBeFalse();
});

it('is active when any narrowing filter is set', function () {
    expect((new CatalogFilters(search: 'veterinariya'))->isActive())->toBeTrue();
    expect((new CatalogFilters(types: [1]))->isActive())->toBeTrue();
    expect((new CatalogFilters(languages: [2]))->isActive())->toBeTrue();
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
