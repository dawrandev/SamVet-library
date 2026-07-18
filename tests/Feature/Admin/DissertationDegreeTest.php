<?php

use App\Models\Dissertation;
use App\Models\DoctoralSpecialty;
use App\Models\JournalIssue;
use App\Models\MasterSpecialty;
use App\Models\ScienceField;

beforeEach(fn () => actingAsAdmin());

// --- New lookup CRUD (ScienceField / DoctoralSpecialty / MasterSpecialty) ---

it('creates, updates and deletes a science field', function () {
    $this->post(route('admin.lookups.science-fields.store'), [
        'name' => 'Veterinariya fanlari',
    ])->assertRedirect(route('admin.lookups.science-fields.index'));

    $field = ScienceField::firstWhere('name', 'Veterinariya fanlari');
    expect($field)->not->toBeNull();

    $this->put(route('admin.lookups.science-fields.update', $field), [
        'name' => 'Biologiya fanlari',
    ])->assertRedirect();
    expect($field->fresh()->name)->toBe('Biologiya fanlari');

    $this->delete(route('admin.lookups.science-fields.destroy', $field))->assertRedirect();
    $this->assertDatabaseMissing('science_fields', ['id' => $field->id]);
});

it('creates, updates and deletes a doctoral specialty', function () {
    $this->post(route('admin.lookups.doctoral-specialties.store'), [
        'name' => '03.00.06-Zoologiya',
    ])->assertRedirect(route('admin.lookups.doctoral-specialties.index'));

    $specialty = DoctoralSpecialty::firstWhere('name', '03.00.06-Zoologiya');
    expect($specialty)->not->toBeNull();

    $this->put(route('admin.lookups.doctoral-specialties.update', $specialty), [
        'name' => '03.00.07-Mikrobiologiya',
    ])->assertRedirect();
    expect($specialty->fresh()->name)->toBe('03.00.07-Mikrobiologiya');

    $this->delete(route('admin.lookups.doctoral-specialties.destroy', $specialty))->assertRedirect();
    $this->assertDatabaseMissing('doctoral_specialties', ['id' => $specialty->id]);
});

it('creates, updates and deletes a master specialty', function () {
    $this->post(route('admin.lookups.master-specialties.store'), [
        'name' => '70710201 - Biotexnologiya',
    ])->assertRedirect(route('admin.lookups.master-specialties.index'));

    $specialty = MasterSpecialty::firstWhere('name', '70710201 - Biotexnologiya');
    expect($specialty)->not->toBeNull();

    $this->put(route('admin.lookups.master-specialties.update', $specialty), [
        'name' => '70710202 - Genetika',
    ])->assertRedirect();
    expect($specialty->fresh()->name)->toBe('70710202 - Genetika');

    $this->delete(route('admin.lookups.master-specialties.destroy', $specialty))->assertRedirect();
    $this->assertDatabaseMissing('master_specialties', ['id' => $specialty->id]);
});

it('lets the inline lookup-create endpoint add a new science field', function () {
    $this->postJson(route('admin.lookups.store'), [
        'type' => 'science_field',
        'name' => 'Agrar fanlar',
    ])->assertCreated()->assertJsonStructure(['id', 'name']);

    expect(ScienceField::where('name', 'Agrar fanlar')->exists())->toBeTrue();
});

// --- Degree-conditional validation ---

it('requires science field and doctoral specialty when degree is phd', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'PhD dissertatsiya',
            'degree' => 'phd',
        ])
        ->assertSessionHasErrors(['science_field_id', 'doctoral_specialty_id']);
});

it('requires science field and doctoral specialty when degree is dsc', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'DSc dissertatsiya',
            'degree' => 'dsc',
        ])
        ->assertSessionHasErrors(['science_field_id', 'doctoral_specialty_id']);
});

it('requires master specialty when degree is master', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'Magistrlik dissertatsiya',
            'degree' => 'master',
        ])
        ->assertSessionHasErrors(['master_specialty_id']);
});

it('does not require any specialty when degree is left blank', function () {
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Turi ko‘rsatilmagan dissertatsiya',
    ])->assertSessionDoesntHaveErrors(['science_field_id', 'doctoral_specialty_id', 'master_specialty_id']);
});

// --- Saving with the correct field set ---

it('saves a phd dissertation with its science field and doctoral specialty', function () {
    $issue = JournalIssue::factory()->create();
    $scienceField = ScienceField::factory()->create();
    $specialty = DoctoralSpecialty::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'PhD ishi',
        'degree' => 'phd',
        'science_field_id' => $scienceField->id,
        'doctoral_specialty_id' => $specialty->id,
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'PhD ishi');
    expect($dissertation)->not->toBeNull()
        ->and($dissertation->degree->value)->toBe('phd')
        ->and($dissertation->science_field_id)->toBe($scienceField->id)
        ->and($dissertation->doctoral_specialty_id)->toBe($specialty->id)
        ->and($dissertation->master_specialty_id)->toBeNull();
});

it('saves a master dissertation with its master specialty, ignoring any doctoral fields sent alongside', function () {
    $issue = JournalIssue::factory()->create();
    $scienceField = ScienceField::factory()->create();
    $doctoralSpecialty = DoctoralSpecialty::factory()->create();
    $masterSpecialty = MasterSpecialty::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Magistrlik ishi',
        'degree' => 'master',
        // Stray doctoral-side values (e.g. leftover from a prior degree toggle) must not be saved.
        'science_field_id' => $scienceField->id,
        'doctoral_specialty_id' => $doctoralSpecialty->id,
        'master_specialty_id' => $masterSpecialty->id,
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Magistrlik ishi');
    expect($dissertation)->not->toBeNull()
        ->and($dissertation->degree->value)->toBe('master')
        ->and($dissertation->master_specialty_id)->toBe($masterSpecialty->id)
        ->and($dissertation->science_field_id)->toBeNull()
        ->and($dissertation->doctoral_specialty_id)->toBeNull();
});

it('saves admin-only inventory and condition fields', function () {
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Inventarli dissertatsiya',
        'inventory_number' => 'INV-D-0001',
        'condition' => 'new',
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Inventarli dissertatsiya');
    expect($dissertation->inventory_number)->toBe('INV-D-0001')
        ->and($dissertation->condition->value)->toBe('new');
});

// --- Form rendering ---

it('shows the degree-conditional fields on the create form', function () {
    $this->get(route('admin.dissertations.create'))
        ->assertSee('Fan nomi')
        ->assertSee('Ixtisoslik shifri va nomi')
        ->assertSee('Mutaxassislik shifri va nomi')
        ->assertSee('Inventari')
        ->assertSee('Holati');
});
