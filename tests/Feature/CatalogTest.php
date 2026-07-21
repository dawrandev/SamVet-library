<?php

use App\Enums\CatalogSort;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Category;
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

it('only lists top-level categories as filter facets, not children', function () {
    $parent = Category::factory()->create(['name' => 'Ota kategoriya']);
    $child = Category::factory()->create(['name' => 'Bola kategoriya', 'parent_id' => $parent->id]);

    $res = $this->get(route('catalog'));

    $labels = collect($res->viewData('categories'))->pluck('label');
    expect($labels)->toContain('Ota kategoriya')
        ->not->toContain('Bola kategoriya');
});

it('rolls up a child category count into its parent facet', function () {
    $parent = Category::factory()->create(['name' => 'Ota kategoriya']);
    $child = Category::factory()->create(['name' => 'Bola kategoriya', 'parent_id' => $parent->id]);
    $book = Book::factory()->create();
    $book->categories()->attach($child->id);

    $res = $this->get(route('catalog'));

    $parentFacet = collect($res->viewData('categories'))->firstWhere('id', $parent->id);
    expect($parentFacet['count'])->toBe(1);
});

it('filtering by a parent category surfaces books tagged only with its child', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $book = Book::factory()->create();
    $book->categories()->attach($child->id);
    Book::factory()->create(); // unrelated book, must not match

    $res = $this->get(route('catalog', ['categories' => [$parent->id]]));

    expect($res->viewData('total'))->toBe(1);
});
