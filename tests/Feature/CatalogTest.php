<?php

use App\Enums\CatalogSort;
use App\Models\Book;
use App\Models\BookCopy;
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

it('filters by copy format (bosma/elektron/brayl)', function () {
    $printBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $printBook->id, 'format' => 'print']);
    $electronicBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $electronicBook->id, 'format' => 'electronic']);

    $res = $this->get(route('catalog', ['formats' => ['print']]));

    expect($res->viewData('total'))->toBe(1);
});

it('shows a copy-format facet with a count per format', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'print']);

    $res = $this->get(route('catalog'));

    $printFacet = collect($res->viewData('formats'))->firstWhere('id', 'print');
    expect($printFacet['count'])->toBe(1);
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

it('lists every category — parent and child alike — as a filter facet', function () {
    $parent = Category::factory()->create(['name' => 'Ota kategoriya']);
    $child = Category::factory()->create(['name' => 'Bola kategoriya', 'parent_id' => $parent->id]);

    $res = $this->get(route('catalog'));

    $facets = collect($res->viewData('categories'));
    $parentFacet = $facets->firstWhere('id', $parent->id);
    $childFacet = $facets->firstWhere('id', $child->id);

    expect($parentFacet)->not->toBeNull()
        ->and($parentFacet['parentId'])->toBeNull()
        ->and($childFacet)->not->toBeNull()
        ->and($childFacet['parentId'])->toBe($parent->id);
});

it('renders each category child under a collapsible dropdown toggle beneath its parent', function () {
    $parent = Category::factory()->create(['name' => 'Ota kategoriya']);
    $child = Category::factory()->create(['name' => 'Bola kategoriya', 'parent_id' => $parent->id]);

    $res = $this->get(route('catalog'));

    $res->assertOk()
        ->assertSee('Ota kategoriya')
        ->assertSee('Bola kategoriya')
        // The collapse toggle button and its Alpine-driven visibility state.
        ->assertSee('x-show="open"', false)
        ->assertSee('@click="open = !open"', false);
});

it('counts a child category facet by only its own directly-tagged books, not its siblings\'', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $sibling = Category::factory()->create(['parent_id' => $parent->id]);
    Book::factory()->create()->categories()->attach($child->id);
    Book::factory()->create()->categories()->attach($sibling->id);

    $res = $this->get(route('catalog'));

    $childFacet = collect($res->viewData('categories'))->firstWhere('id', $child->id);
    expect($childFacet['count'])->toBe(1);
});

it('filtering by a child category surfaces only books tagged with that exact child', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $sibling = Category::factory()->create(['parent_id' => $parent->id]);
    $book = Book::factory()->create();
    $book->categories()->attach($child->id);
    Book::factory()->create()->categories()->attach($sibling->id); // must not match

    $res = $this->get(route('catalog', ['categories' => [$child->id]]));

    expect($res->viewData('total'))->toBe(1);
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
