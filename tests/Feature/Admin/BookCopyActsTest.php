<?php

use App\Models\Book;
use App\Models\BookCopy;

beforeEach(fn () => actingAsAdmin());

it('creates a copy with plain-text acquisition and disposal act fields', function () {
    $book = Book::factory()->create();

    $this->post(route('admin.books.copies.store', $book), [
        '_copy_form' => 'store',
        'inventory_number' => 'INV-ACT-1',
        'format' => 'print',
        'condition' => 'new',
        'status' => 'available',
        'acquisition_act_number' => 'KA-12',
        'acquisition_act_at' => '2026-07-01T10:30',
        'disposal_act_number' => 'CA-7',
        'disposal_act_at' => '2026-07-15T14:00',
    ])->assertRedirect();

    $copy = BookCopy::firstWhere('inventory_number', 'INV-ACT-1');
    expect($copy)->not->toBeNull()
        ->and($copy->acquisition_act_number)->toBe('KA-12')
        ->and($copy->acquisition_act_at->format('Y-m-d H:i'))->toBe('2026-07-01 10:30')
        ->and($copy->disposal_act_number)->toBe('CA-7')
        ->and($copy->disposal_act_at->format('Y-m-d H:i'))->toBe('2026-07-15 14:00');
});

it('saves a copy with no act info at all', function () {
    $book = Book::factory()->create();

    $this->post(route('admin.books.copies.store', $book), [
        '_copy_form' => 'store',
        'inventory_number' => 'INV-ACT-2',
        'format' => 'print',
        'condition' => 'new',
        'status' => 'available',
    ])->assertRedirect();

    $copy = BookCopy::firstWhere('inventory_number', 'INV-ACT-2');
    expect($copy->acquisition_act_number)->toBeNull()
        ->and($copy->acquisition_act_at)->toBeNull()
        ->and($copy->disposal_act_number)->toBeNull()
        ->and($copy->disposal_act_at)->toBeNull();
});

it('updates a copy’s act fields', function () {
    $book = Book::factory()->create();
    $copy = BookCopy::factory()->create(['book_id' => $book->id]);

    $this->put(route('admin.books.copies.update', [$book, $copy]), [
        '_copy_form' => 'edit',
        'inventory_number' => $copy->inventory_number,
        'format' => $copy->format->value,
        'condition' => $copy->condition->value,
        'status' => $copy->status->value,
        'acquisition_act_number' => 'YANGI-KA',
        'acquisition_act_at' => '2026-08-01T09:00',
    ])->assertRedirect();

    expect($copy->fresh()->acquisition_act_number)->toBe('YANGI-KA');
});

it('shows the act number and date on the book show page', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create([
        'book_id' => $book->id,
        'acquisition_act_number' => 'KA-99',
        'acquisition_act_at' => '2026-07-01 10:30:00',
    ]);

    $this->get(route('admin.books.show', $book))
        ->assertSee('KA-99')
        ->assertSee('01.07.2026 10:30');
});
