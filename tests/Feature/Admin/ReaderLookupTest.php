<?php

use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('finds a reader by id number with student-labeled affiliation fields', function () {
    $reader = Reader::factory()->create([
        'type' => 'bachelor',
        'id_number' => 'BT0199001',
        'full_name' => 'Test Talaba',
        'affiliation_place_id' => AffiliationPlace::factory()->create(['name' => 'Veterinariya fakulteti'])->id,
        'affiliation_unit_id' => AffiliationUnit::factory()->create(['name' => 'Veterinariya'])->id,
        'affiliation_group_id' => AffiliationGroup::factory()->create(['name' => '2-21'])->id,
        'status' => 'active',
    ]);

    $this->getJson(route('admin.readers.lookup', ['id_number' => 'BT0199001']))
        ->assertOk()
        ->assertJson([
            'found' => true,
            'reader_id' => $reader->id,
            'full_name' => 'Test Talaba',
            'can_borrow' => true,
            'affiliation' => [
                'place_label' => 'O‘qish joyi',
                'place' => 'Veterinariya fakulteti',
                'unit_label' => 'Mutaxassisligi',
                'unit' => 'Veterinariya',
                'group_label' => 'Guruhi',
                'group' => '2-21',
            ],
        ]);
});

it('finds a reader by id number with staff-labeled affiliation fields', function () {
    Reader::factory()->create([
        'type' => 'professor',
        'id_number' => 'PF0199002',
        'affiliation_place_id' => AffiliationPlace::factory()->create(['name' => 'Kafedra'])->id,
        'affiliation_unit_id' => AffiliationUnit::factory()->create(['name' => 'Ichki kasalliklar'])->id,
        'affiliation_group_id' => AffiliationGroup::factory()->create(['name' => 'Dotsent'])->id,
        'status' => 'active',
    ]);

    $response = $this->getJson(route('admin.readers.lookup', ['id_number' => 'PF0199002']))
        ->assertOk()
        ->json();

    expect($response['affiliation']['place_label'])->toBe('Ish joyi')
        ->and($response['affiliation']['unit_label'])->toBe('Bo‘limi')
        ->and($response['affiliation']['group_label'])->toBe('Lavozimi');
});

it('reports found=false for an unknown id number', function () {
    $this->getJson(route('admin.readers.lookup', ['id_number' => 'NOPE']))
        ->assertOk()
        ->assertJson(['found' => false]);
});

it('reports can_borrow=false for a blocked reader', function () {
    Reader::factory()->create([
        'id_number' => 'BL0199003',
        'status' => 'blocked',
    ]);

    $this->getJson(route('admin.readers.lookup', ['id_number' => 'BL0199003']))
        ->assertOk()
        ->assertJson(['found' => true, 'can_borrow' => false]);
});

it('shows the Berish button only for an available copy on the book show page', function () {
    $book = Book::factory()->create();
    $available = BookCopy::factory()->create(['book_id' => $book->id, 'status' => 'available', 'inventory_number' => 'INV-LEND-1']);
    $borrowed = BookCopy::factory()->create(['book_id' => $book->id, 'status' => 'borrowed', 'inventory_number' => 'INV-LEND-2']);

    $response = $this->get(route('admin.books.show', $book));

    $response->assertOk();
    $body = $response->getContent();

    expect(substr_count($body, "openLend('INV-LEND-1')"))->toBe(1)
        ->and($body)->not->toContain("openLend('INV-LEND-2')");
});

it('lends a book from the book page using the id-number lookup, then the reusable loan-store route', function () {
    $reader = Reader::factory()->create(['id_number' => 'LN0199001', 'status' => 'active']);
    $copy = BookCopy::factory()->create(['status' => 'available', 'inventory_number' => 'INV-LEND-FLOW']);

    // Step 1: the new lookup the "Berish" modal calls.
    $lookup = $this->getJson(route('admin.readers.lookup', ['id_number' => 'LN0199001']))->json();
    expect($lookup['can_borrow'])->toBeTrue();

    // Step 2: the modal posts to the SAME existing route the reader-side flow already uses.
    $this->post(route('admin.readers.loans.store', $lookup['reader_id']), [
        'inventory_number' => 'INV-LEND-FLOW',
        'due_at' => now()->addDays(15)->format('Y-m-d'),
    ])->assertRedirect();

    $loan = Loan::where('reader_id', $reader->id)->first();
    expect($loan)->not->toBeNull()
        ->and($loan->loanable_id)->toBe($copy->id);
    expect($copy->fresh()->status->value)->toBe('borrowed');
});
