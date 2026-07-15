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

it('creates a journal with a periodicity unit and a free "necha marta" count', function () {
    $this->post(route('admin.journals.store'), [
        'name' => 'Haftalik axborotnoma',
        'kind' => 'journal',
        'periodicity' => 'weekly',
        'periodicity_count' => 3,
    ])->assertRedirect();

    $journal = Journal::firstWhere('name', 'Haftalik axborotnoma');
    expect($journal->periodicity->value)->toBe('weekly')
        ->and($journal->periodicity_count)->toBe(3);
});

it('rejects a periodicity count outside the valid range', function () {
    $this->from(route('admin.journals.create'))
        ->post(route('admin.journals.store'), [
            'name' => 'X',
            'kind' => 'journal',
            'periodicity_count' => 32,
        ])
        ->assertSessionHasErrors('periodicity_count');
});

it('shows the combined periodicity ("necha marta / birlik") on the journal show page', function () {
    $journal = Journal::factory()->create([
        'periodicity' => 'weekly',
        'periodicity_count' => 5,
    ]);

    $this->get(route('admin.journals.show', $journal))
        ->assertSee('5 marta / Haftalik');
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

it('separates journals and newspapers in the index list (?kind=)', function () {
    Journal::factory()->create(['name' => 'Ilmiy jurnal', 'kind' => 'journal']);
    Journal::factory()->create(['name' => 'Kunlik gazeta', 'kind' => 'newspaper']);

    $this->get(route('admin.journals.index', ['kind' => 'journal']))
        ->assertSee('Ilmiy jurnal')
        ->assertDontSee('Kunlik gazeta')
        ->assertSee('Jurnallar');

    $this->get(route('admin.journals.index', ['kind' => 'newspaper']))
        ->assertSee('Kunlik gazeta')
        ->assertDontSee('Ilmiy jurnal')
        ->assertSee('Gazetalar');
});

it('fixes the kind on create instead of asking again (navigation already decided it)', function () {
    // Coming from "Gazetalar" → "Yangi gazeta": the kind dropdown is redundant
    // (you already told the system your intent), so it's a hidden field, not a select.
    $this->get(route('admin.journals.create', ['kind' => 'newspaper']))
        ->assertSee('Yangi gazeta')
        ->assertDontSee('<select name="kind"', false)
        ->assertSee('<input type="hidden" name="kind" value="newspaper"', false);
});

it('keeps the kind select editable when correcting an existing journal', function () {
    $journal = Journal::factory()->create(['kind' => 'newspaper']);

    $this->get(route('admin.journals.edit', $journal))
        ->assertSee('<select name="kind"', false)
        ->assertSee('value="newspaper" selected', false);
});
