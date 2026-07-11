<?php

use App\Enums\CatalogSort;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Language;

it('renders the catalog', function () {
    Book::factory()->count(3)->create();

    $this->get(route('catalog'))->assertOk();
});

it('filters by book type', function () {
    $type = BookType::factory()->create();
    Book::factory()->count(2)->create(['book_type_id' => $type->id]);
    Book::factory()->count(3)->create();

    $res = $this->get(route('catalog', ['types' => [$type->id]]));

    expect($res->viewData('total'))->toBe(2);
});

it('filters by language', function () {
    $lang = Language::factory()->create();
    Book::factory()->count(4)->create(['language_id' => $lang->id]);
    Book::factory()->count(1)->create();

    $res = $this->get(route('catalog', ['languages' => [$lang->id]]));

    expect($res->viewData('total'))->toBe(4);
});

it('searches by title', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);
    Book::factory()->create(['title' => 'Iqtisodiyot nazariyasi']);

    $res = $this->get(route('catalog', ['q' => 'veterinariya']));

    expect($res->viewData('total'))->toBe(1);
});

it('filters by publication year range', function () {
    Book::factory()->create(['publication_year' => 2000]);
    Book::factory()->create(['publication_year' => 2020]);

    expect($this->get(route('catalog', ['year_from' => 2015]))->viewData('total'))->toBe(1);
});

it('validates the sort parameter', function () {
    $this->get(route('catalog', ['sort' => 'not-a-sort']))->assertSessionHasErrors('sort');
    $this->get(route('catalog', ['sort' => CatalogSort::Popular->value]))->assertOk();
});
