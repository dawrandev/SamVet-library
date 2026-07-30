<?php

use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use App\Enums\LoanMaterialType;
use App\Models\BookCopy;
use App\Models\Journal;
use App\Models\JournalCopy;
use App\Models\JournalIssue;
use App\Models\Loan;
use App\Models\Reader;
use App\Services\LoanService;

beforeEach(fn () => actingAsAdmin());

it('issues a book copy and snapshots its condition', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $copy = BookCopy::factory()->create(['condition' => [CopyCondition::Old->value]]);

    $loan = app(LoanService::class)->issueByInventory($reader, $copy->inventory_number, now()->addDays(10)->format('Y-m-d'), null);

    expect($loan->loanable_type)->toBe('book_copy')
        ->and($loan->loanable_id)->toBe($copy->id)
        ->and($loan->issued_condition->all())->toBe([CopyCondition::Old])
        ->and($loan->materialType())->toBe(LoanMaterialType::Book);

    expect($copy->fresh()->status)->toBe(CopyStatus::Borrowed);
});

it('issues a journal copy when no book copy matches the inventory number', function () {
    $reader = Reader::factory()->create(['status' => 'active']);
    $issue = JournalIssue::factory()->for(Journal::factory())->create();
    $copy = JournalCopy::factory()->create(['journal_issue_id' => $issue->id, 'condition' => [CopyCondition::New->value]]);

    $loan = app(LoanService::class)->issueByInventory($reader, $copy->inventory_number, now()->addDays(10)->format('Y-m-d'), null);

    expect($loan->loanable_type)->toBe('journal_copy')
        ->and($loan->loanable_id)->toBe($copy->id)
        ->and($loan->materialType())->toBe(LoanMaterialType::Journal);

    expect($copy->fresh()->status)->toBe(CopyStatus::Borrowed);
});

it('shows Gazeta as the material type for a newspaper-kind journal copy', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->newspaper()->create();
    $issue = JournalIssue::factory()->for($journal)->create();
    $copy = JournalCopy::factory()->create(['journal_issue_id' => $issue->id]);

    $loan = Loan::factory()->create([
        'reader_id' => $reader->id,
        'loanable_type' => 'journal_copy',
        'loanable_id' => $copy->id,
    ]);

    expect($loan->materialType())->toBe(LoanMaterialType::Newspaper);
});

it('returns a loan with recorded conditions and replaces the live copy condition', function () {
    $reader = Reader::factory()->create();
    $copy = BookCopy::factory()->borrowed()->create(['condition' => [CopyCondition::New->value]]);
    $loan = Loan::factory()->create([
        'reader_id' => $reader->id,
        'loanable_id' => $copy->id,
        'issued_condition' => [CopyCondition::New->value],
    ]);

    $this->patch(route('admin.loans.return', $loan), [
        'returned_condition' => [CopyCondition::Torn->value, CopyCondition::PagesIncomplete->value],
    ])->assertRedirect(route('admin.readers.show', $reader));

    $loan->refresh();
    expect($loan->status->value)->toBe('returned')
        ->and($loan->returned_condition->all())->toBe([CopyCondition::Torn, CopyCondition::PagesIncomplete])
        ->and($loan->returned_at)->not->toBeNull();

    expect($copy->fresh()->condition->all())->toBe([CopyCondition::Torn, CopyCondition::PagesIncomplete])
        ->and($copy->fresh()->status)->toBe(CopyStatus::Available);
});

it('returns a loan without any condition without touching the copy condition', function () {
    $reader = Reader::factory()->create();
    $copy = BookCopy::factory()->borrowed()->create(['condition' => [CopyCondition::New->value]]);
    $loan = Loan::factory()->create(['reader_id' => $reader->id, 'loanable_id' => $copy->id]);

    $this->patch(route('admin.loans.return', $loan))->assertRedirect();

    expect($copy->fresh()->condition->first())->toBe(CopyCondition::New)
        ->and($copy->fresh()->status)->toBe(CopyStatus::Available);
});

it('looks up a book copy by inventory number', function () {
    $copy = BookCopy::factory()->create();

    $this->getJson(route('admin.copies.lookup', ['inventory' => $copy->inventory_number]))
        ->assertJson(['found' => true, 'type' => 'book']);
});

it('looks up a journal copy by inventory number when no book copy matches', function () {
    $copy = JournalCopy::factory()->create();

    $this->getJson(route('admin.copies.lookup', ['inventory' => $copy->inventory_number]))
        ->assertJson(['found' => true, 'type' => 'journal_copy']);
});

it('paginates a reader materials list at 10 per page and filters by material type', function () {
    $reader = Reader::factory()->create();
    Loan::factory()->count(12)->create(['reader_id' => $reader->id]);
    $journalLoan = Loan::factory()->journalCopy()->create(['reader_id' => $reader->id]);

    $page = app(LoanService::class)->paginateForReader($reader->id, []);
    expect($page->perPage())->toBe(10)
        ->and($page->total())->toBe(13);

    $filtered = app(LoanService::class)->paginateForReader($reader->id, ['material_type' => 'journal']);
    expect($filtered->total())->toBe(1)
        ->and($filtered->items()[0]->id)->toBe($journalLoan->id);
});

it('shows the material type badge on the reader show page for each loan kind', function () {
    $reader = Reader::factory()->create();
    Loan::factory()->create(['reader_id' => $reader->id]);
    Loan::factory()->journalCopy()->create(['reader_id' => $reader->id]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Kitob')
        ->assertSee('Jurnal');
});

it('shows every issued condition tag for a loan on the reader show page', function () {
    $reader = Reader::factory()->create();
    $copy = BookCopy::factory()->borrowed()->create([
        'condition' => [CopyCondition::Old->value, CopyCondition::PagesIncomplete->value],
    ]);
    Loan::factory()->create([
        'reader_id' => $reader->id,
        'loanable_id' => $copy->id,
        'issued_condition' => [CopyCondition::Old->value, CopyCondition::PagesIncomplete->value],
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee(CopyCondition::Old->label())
        ->assertSee(CopyCondition::PagesIncomplete->label());
});
