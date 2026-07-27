<?php

use App\Models\Category;

beforeEach(function () {
    actingAsAdmin();
    // A couple of top-level categories are seeded by migration
    // (2026_07_25_155100_seed_foreign_and_fiction_top_level_categories) —
    // cleared so each test's sibling-group assumptions are self-contained.
    Category::query()->delete();
});

it('creates a category that appends to the end of its sibling group, not the front', function () {
    Category::factory()->create(['name' => ['uz' => 'Birinchi'], 'sort_order' => 0]);
    Category::factory()->create(['name' => ['uz' => 'Ikkinchi'], 'sort_order' => 1]);

    $this->post(route('admin.lookups.categories.store'), [
        'name' => ['uz' => 'Uchinchi', 'ru' => 'Uchinchi', 'kk' => 'Uchinchi'],
    ])->assertRedirect();

    $new = Category::firstWhere('name->uz', 'Uchinchi');
    expect($new->sort_order)->toBe(2);
});

it('moves a top-level category up, swapping sort_order with its previous sibling', function () {
    $first = Category::factory()->create(['name' => ['uz' => 'Birinchi'], 'sort_order' => 0]);
    $second = Category::factory()->create(['name' => ['uz' => 'Ikkinchi'], 'sort_order' => 1]);

    $this->patch(route('admin.lookups.categories.move-up', $second))->assertRedirect();

    expect($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);
});

it('moves a top-level category down, swapping sort_order with its next sibling', function () {
    $first = Category::factory()->create(['name' => ['uz' => 'Birinchi'], 'sort_order' => 0]);
    $second = Category::factory()->create(['name' => ['uz' => 'Ikkinchi'], 'sort_order' => 1]);

    $this->patch(route('admin.lookups.categories.move-down', $first))->assertRedirect();

    expect($first->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0);
});

it('does nothing when moving the first category up or the last category down', function () {
    $first = Category::factory()->create(['name' => ['uz' => 'Birinchi'], 'sort_order' => 0]);
    $last = Category::factory()->create(['name' => ['uz' => 'Oxirgi'], 'sort_order' => 1]);

    $this->patch(route('admin.lookups.categories.move-up', $first));
    $this->patch(route('admin.lookups.categories.move-down', $last));

    expect($first->fresh()->sort_order)->toBe(0)
        ->and($last->fresh()->sort_order)->toBe(1);
});

it('only reorders within the same sibling group — a child never swaps with a top-level category', function () {
    $parentA = Category::factory()->create(['name' => ['uz' => 'Ota A'], 'sort_order' => 0]);
    $parentB = Category::factory()->create(['name' => ['uz' => 'Ota B'], 'sort_order' => 1]);
    $childOfA = Category::factory()->create(['name' => ['uz' => 'Bola'], 'parent_id' => $parentA->id, 'sort_order' => 0]);

    // The only child of parentA has no siblings — moving it up/down is a no-op,
    // it must never swap with parentB or any other top-level category.
    $this->patch(route('admin.lookups.categories.move-up', $childOfA));
    $this->patch(route('admin.lookups.categories.move-down', $childOfA));

    expect($childOfA->fresh()->sort_order)->toBe(0)
        ->and($childOfA->fresh()->parent_id)->toBe($parentA->id)
        ->and($parentA->fresh()->sort_order)->toBe(0)
        ->and($parentB->fresh()->sort_order)->toBe(1);
});

it('respects sort_order for the public homepage section tiles', function () {
    $a = Category::factory()->create(['name' => ['uz' => 'Kategoriya A'], 'sort_order' => 1]);
    $b = Category::factory()->create(['name' => ['uz' => 'Kategoriya B'], 'sort_order' => 0]);

    $body = $this->get(route('home'))->getContent();

    // B has the lower sort_order, so it must appear first in the markup.
    expect(strpos($body, 'Kategoriya B'))->toBeLessThan(strpos($body, 'Kategoriya A'));
});

it('preserves manual sort_order when editing a category’s name (does not reset to the back)', function () {
    Category::factory()->create(['name' => ['uz' => 'Birinchi'], 'sort_order' => 0]);
    $second = Category::factory()->create(['name' => ['uz' => 'Ikkinchi'], 'sort_order' => 1]);

    $this->put(route('admin.lookups.categories.update', $second), [
        'name' => ['uz' => 'Ikkinchi (tahrirlangan)', 'ru' => 'x', 'kk' => 'x'],
    ])->assertRedirect();

    expect($second->fresh()->sort_order)->toBe(1);
});
