<?php

use App\Enums\ReaderStatus;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('finishes usage and stores the reason when the reader has no outstanding loans', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->patch(route('admin.readers.finish', $reader), [
        'left_reason' => 'O‘qishni bitirgan',
    ])->assertRedirect(route('admin.readers.index'));

    $reader->refresh();
    expect($reader->status)->toBe(ReaderStatus::Left)
        ->and($reader->left_reason)->toBe('O‘qishni bitirgan');
});

it('requires a reason to finish usage', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->from(route('admin.readers.show', $reader))
        ->patch(route('admin.readers.finish', $reader), [])
        ->assertSessionHasErrors('left_reason');

    expect($reader->fresh()->status)->toBe(ReaderStatus::Active);
});

it('refuses to finish usage while the reader has an unreturned book', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    Loan::create([
        'reader_id' => $reader->id,
        'book_copy_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->patch(route('admin.readers.finish', $reader), [
        'left_reason' => 'Ishdan ketti',
    ])->assertSessionHasErrors('left_reason');

    expect($reader->fresh()->status)->toBe(ReaderStatus::Active);
});

it('allows finishing again once the outstanding book has been returned', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    $loan = Loan::create([
        'reader_id' => $reader->id,
        'book_copy_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    // Return the book.
    $loan->update(['status' => 'returned', 'returned_at' => now()]);

    $this->patch(route('admin.readers.finish', $reader), [
        'left_reason' => 'Dekret',
    ])->assertRedirect(route('admin.readers.index'));

    expect($reader->fresh()->status)->toBe(ReaderStatus::Left);
});

it('shows the outstanding-book warning instead of the reason form on the show page', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    $copy->book->update(['title' => 'Qarzdorlik sinov kitobi']);
    Loan::create([
        'reader_id' => $reader->id,
        'book_copy_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Qarzdorlik sinov kitobi')
        ->assertSee('Foydalanuvchida qaytarilmagan kitob');
});
