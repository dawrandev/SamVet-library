<?php

use App\Models\Dissertation;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => actingAsAdmin());

it('creates a standalone dissertation, with no journal/issue involved', function () {
    $this->post(route('admin.dissertations.store'), [
        'title' => 'Test dissertatsiya',
        'author' => 'N. Testov',
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Test dissertatsiya');
    expect($dissertation)->not->toBeNull()
        ->and($dissertation->slug)->not->toBeEmpty();
});

it('requires only a title — not a journal, not even an author', function () {
    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [])
        ->assertSessionHasErrors(['title'])
        ->assertSessionDoesntHaveErrors(['author', 'journal_issue_id']);
});

it('does not show a journal/issue field on the create form', function () {
    $this->get(route('admin.dissertations.create'))
        ->assertDontSee('journal_issue_id', false)
        ->assertDontSee(__('Jurnal va son'));
});

it('does not show journal information on the dissertation show page', function () {
    $dissertation = Dissertation::factory()->create();

    $this->get(route('admin.dissertations.show', $dissertation))
        ->assertDontSee(__('Jurnal ma’lumotlari'));
});

it('does not show a resurs sohasi field anywhere — never in the librarian’s mockup', function () {
    $dissertation = Dissertation::factory()->create();

    $this->get(route('admin.dissertations.create'))
        ->assertDontSee('resource_field_id', false)
        ->assertDontSee(__('Resurs sohasi'));

    $this->get(route('admin.dissertations.edit', $dissertation))
        ->assertDontSee('resource_field_id', false)
        ->assertDontSee(__('Resurs sohasi'));

    $this->get(route('admin.dissertations.index'))
        ->assertDontSee('resource_field_id', false)
        ->assertDontSee(__('Resurs sohasi'));

    $this->get(route('admin.dissertations.show', $dissertation))
        ->assertDontSee(__('Resurs sohasi'));
});

it('saves the kirish/chiqish akti (acquisition/disposal act) number and date, like a book copy', function () {
    $this->post(route('admin.dissertations.store'), [
        'title' => 'Akt maydonlari sinovi',
        'inventory_number' => 'DS-00456',
        'acquisition_act_number' => 'KA-0012',
        'acquisition_act_at' => '2024-03-10',
        'disposal_act_number' => 'CHA-0003',
        'disposal_act_at' => '2025-01-15',
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Akt maydonlari sinovi');
    expect($dissertation->acquisition_act_number)->toBe('KA-0012')
        ->and($dissertation->acquisition_act_at->format('Y-m-d'))->toBe('2024-03-10')
        ->and($dissertation->disposal_act_number)->toBe('CHA-0003')
        ->and($dissertation->disposal_act_at->format('Y-m-d'))->toBe('2025-01-15');

    $this->get(route('admin.dissertations.show', $dissertation))
        ->assertSee('KA-0012')
        ->assertSee('10.03.2024')
        ->assertSee('CHA-0003')
        ->assertSee('15.01.2025');
});

it('leaves the acquisition/disposal act fields nullable', function () {
    $this->post(route('admin.dissertations.store'), [
        'title' => 'Aktsiz dissertatsiya',
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Aktsiz dissertatsiya');
    expect($dissertation->acquisition_act_number)->toBeNull()
        ->and($dissertation->acquisition_act_at)->toBeNull()
        ->and($dissertation->disposal_act_number)->toBeNull()
        ->and($dissertation->disposal_act_at)->toBeNull();
});

it('rejects a non-pdf file', function () {
    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [
            'title' => 'X',
            'author' => 'Y',
            'electronic_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('electronic_file');
});

it('deletes a dissertation', function () {
    $dissertation = Dissertation::factory()->create();

    $this->delete(route('admin.dissertations.destroy', $dissertation))->assertRedirect();

    $this->assertDatabaseMissing('dissertations', ['id' => $dissertation->id]);
});

it('downloads an Excel export of the dissertation list', function () {
    Dissertation::factory()->count(2)->create();

    $response = $this->get(route('admin.dissertations.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});
