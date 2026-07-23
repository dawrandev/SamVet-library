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
