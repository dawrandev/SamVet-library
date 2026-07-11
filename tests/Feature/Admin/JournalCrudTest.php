<?php

use App\Models\Journal;
use App\Models\JournalType;
use App\Models\Language;
use App\Models\PublicationPlace;

beforeEach(fn () => actingAsAdmin());

it('creates a journal', function () {
    $type = JournalType::factory()->create();
    $place = PublicationPlace::factory()->create();
    $language = Language::factory()->create();

    $this->post(route('admin.journals.store'), [
        'name' => 'Veterinariya axborotnomasi',
        'kind' => 'journal',
        'journal_type_id' => $type->id,
        'language_id' => $language->id,
        // Publisher is translatable free text; place of publication is a lookup.
        'publisher' => ['uz' => 'SDVUNF nashriyoti'],
        'publication_place_id' => $place->id,
        'issn' => '1234-5678',
        'periodicity' => 'monthly',
    ])->assertRedirect();

    $journal = Journal::firstWhere('name', 'Veterinariya axborotnomasi');
    expect($journal)->not->toBeNull()
        ->and($journal->kind->value)->toBe('journal')
        ->and($journal->publisher)->toBe('SDVUNF nashriyoti')
        ->and($journal->publication_place_id)->toBe($place->id)
        ->and($journal->slug)->not->toBeEmpty();
});

it('creates a newspaper (kind = newspaper)', function () {
    $this->post(route('admin.journals.store'), [
        'name' => 'Universitet gazetasi',
        'kind' => 'newspaper',
    ])->assertRedirect();

    expect(Journal::firstWhere('name', 'Universitet gazetasi')->kind->value)
        ->toBe('newspaper');
});

it('requires a name and a kind', function () {
    $this->from(route('admin.journals.create'))
        ->post(route('admin.journals.store'), [])
        ->assertSessionHasErrors(['name', 'kind']);
});

it('rejects an invalid kind', function () {
    $this->from(route('admin.journals.create'))
        ->post(route('admin.journals.store'), [
            'name' => 'X',
            'kind' => 'magazine', // not a PublicationKind case
        ])
        ->assertSessionHasErrors('kind');
});

it('updates a journal', function () {
    $journal = Journal::factory()->create(['name' => 'Eski nom']);

    $this->put(route('admin.journals.update', $journal), [
        'name' => 'Yangi nom',
        'kind' => $journal->kind->value,
    ])->assertRedirect();

    expect($journal->fresh()->name)->toBe('Yangi nom');
});

it('deletes a journal', function () {
    $journal = Journal::factory()->create();

    $this->delete(route('admin.journals.destroy', $journal))->assertRedirect();

    $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
});
