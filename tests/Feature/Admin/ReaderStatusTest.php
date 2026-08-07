<?php

use App\Enums\ReaderStatus;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('finishes usage and stores the reason and timestamp when the reader has no outstanding loans', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->patch(route('admin.readers.finish', $reader), [
        'left_reason' => 'O‘qishni bitirgan',
    ])->assertRedirect(route('admin.readers.index'));

    $reader->refresh();
    expect($reader->status)->toBe(ReaderStatus::Left)
        ->and($reader->left_reason)->toBe('O‘qishni bitirgan')
        ->and($reader->left_at)->not->toBeNull();
});

it('shows the finish reason and timestamp on the show page afterward', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->patch(route('admin.readers.finish', $reader), [
        'left_reason' => 'Ishdan bo‘shadi',
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Foydalanish tugatilgan')
        ->assertSee('Ishdan bo‘shadi')
        ->assertSee($reader->fresh()->left_at->format('d.m.Y'));
});

it('clears the left reason and timestamp when a finished reader is restored', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $this->patch(route('admin.readers.finish', $reader), ['left_reason' => 'Vaqtincha']);

    $this->patch(route('admin.readers.restore', $reader))->assertRedirect();

    $reader->refresh();
    expect($reader->status)->toBe(ReaderStatus::Active)
        ->and($reader->left_reason)->toBeNull()
        ->and($reader->left_at)->toBeNull();
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
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
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
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
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
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Qarzdorlik sinov kitobi')
        ->assertSee('Foydalanuvchida qaytarilmagan kitob');
});

// --- Block: debt guard + required reason ---

it('blocks a reader and stores the reason when the reader has no outstanding loans', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->patch(route('admin.readers.block', $reader), [
        'block_reason' => 'Qoidabuzarlik',
    ])->assertRedirect(route('admin.readers.show', $reader));

    $reader->refresh();
    expect($reader->status)->toBe(ReaderStatus::Blocked)
        ->and($reader->block_reason)->toBe('Qoidabuzarlik');
});

it('requires a reason to block a reader', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->from(route('admin.readers.show', $reader))
        ->patch(route('admin.readers.block', $reader), [])
        ->assertSessionHasErrors('block_reason');

    expect($reader->fresh()->status)->toBe(ReaderStatus::Active);
});

it('refuses to block a reader while they have an unreturned book', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    Loan::create([
        'reader_id' => $reader->id,
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->patch(route('admin.readers.block', $reader), [
        'block_reason' => 'Qoidabuzarlik',
    ])->assertSessionHasErrors('block_reason');

    expect($reader->fresh()->status)->toBe(ReaderStatus::Active);
});

// --- Delete: debt guard ---

it('deletes a reader with no outstanding loans', function () {
    $reader = Reader::factory()->create(['status' => 'active']);

    $this->delete(route('admin.readers.destroy', $reader))
        ->assertRedirect(route('admin.readers.index'));

    $this->assertDatabaseMissing('readers', ['id' => $reader->id]);
});

it('refuses to delete a reader while they have an unreturned book', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    Loan::create([
        'reader_id' => $reader->id,
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->from(route('admin.readers.show', $reader))
        ->delete(route('admin.readers.destroy', $reader))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('readers', ['id' => $reader->id]);
});

it('shows the outstanding-book warning on the show page instead of the delete confirmation', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create();
    $copy->book->update(['title' => 'O‘chirish sinov kitobi']);
    Loan::create([
        'reader_id' => $reader->id,
        'loanable_type' => 'book_copy',
        'loanable_id' => $copy->id,
        'issued_at' => now(),
        'due_at' => now()->addDays(14),
        'status' => 'on_loan',
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('O‘chirish sinov kitobi');
});
