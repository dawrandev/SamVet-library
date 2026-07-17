<?php

use App\Models\Book;
use App\Models\ContributorRole;
use App\Models\Dissertation;
use App\Models\JournalIssue;
use App\Services\ContributorService;

beforeEach(fn () => actingAsAdmin());

// --- ContributorRole lookup CRUD ---

it('creates a contributor role', function () {
    $this->post(route('admin.lookups.contributor-roles.store'), [
        'name' => 'Sharhlovchi',
    ])->assertRedirect(route('admin.lookups.contributor-roles.index'));

    expect(ContributorRole::where('name', 'Sharhlovchi')->exists())->toBeTrue();
});

it('updates a contributor role', function () {
    $role = ContributorRole::factory()->create(['name' => 'Eski nom']);

    $this->put(route('admin.lookups.contributor-roles.update', $role), [
        'name' => 'Yangi nom',
    ])->assertRedirect();

    expect($role->fresh()->name)->toBe('Yangi nom');
});

it('deletes an unused contributor role', function () {
    $role = ContributorRole::factory()->create();

    $this->delete(route('admin.lookups.contributor-roles.destroy', $role))->assertRedirect();

    $this->assertDatabaseMissing('contributor_roles', ['id' => $role->id]);
});

it('refuses to delete a contributor role that is currently assigned', function () {
    $role = ContributorRole::factory()->create();
    $book = Book::factory()->create();
    $book->contributors()->create(['contributor_role_id' => $role->id, 'name' => 'Test I.']);

    $this->delete(route('admin.lookups.contributor-roles.destroy', $role));

    $this->assertDatabaseHas('contributor_roles', ['id' => $role->id]);
});

it('lets the inline lookup-create endpoint add a new contributor role', function () {
    $this->postJson(route('admin.lookups.store'), [
        'type' => 'contributor_role',
        'name' => 'Loyihachi',
    ])->assertCreated()->assertJsonStructure(['id', 'name']);

    expect(ContributorRole::where('name', 'Loyihachi')->exists())->toBeTrue();
});

// --- ContributorService::sync() ---

it('creates contributor rows and skips incomplete ones', function () {
    $book = Book::factory()->create();
    $roleA = ContributorRole::factory()->create();
    $roleB = ContributorRole::factory()->create();

    app(ContributorService::class)->sync($book, [
        ['contributor_role_id' => $roleA->id, 'name' => 'Aliyev A.'],
        ['contributor_role_id' => $roleB->id, 'name' => ''], // incomplete — skipped
        ['contributor_role_id' => '', 'name' => 'No role'],   // incomplete — skipped
    ]);

    expect($book->contributors)->toHaveCount(1)
        ->and($book->contributors->first()->name)->toBe('Aliyev A.');
});

it('replaces the previous contributor set on re-sync, not appends', function () {
    $book = Book::factory()->create();
    $role = ContributorRole::factory()->create();

    app(ContributorService::class)->sync($book, [
        ['contributor_role_id' => $role->id, 'name' => 'Birinchi'],
    ]);
    app(ContributorService::class)->sync($book->fresh(), [
        ['contributor_role_id' => $role->id, 'name' => 'Ikkinchi'],
    ]);

    $book->refresh();
    expect($book->contributors)->toHaveCount(1)
        ->and($book->contributors->first()->name)->toBe('Ikkinchi');
});

// --- Per-material save flows ---

it('saves a book with contributors but no author', function () {
    $role = ContributorRole::factory()->create(['name' => 'Muharrir']);

    $this->post(route('admin.books.store'), [
        'title' => 'Muharriri bor, muallifi yo‘q kitob',
        'author_ids' => [],
        'contributors' => [
            ['contributor_role_id' => $role->id, 'name' => 'Tahrirchi T.'],
        ],
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Muharriri bor, muallifi yo‘q kitob');
    expect($book)->not->toBeNull()
        ->and($book->authors)->toHaveCount(0)
        ->and($book->contributors)->toHaveCount(1)
        ->and($book->contributors->first()->name)->toBe('Tahrirchi T.')
        ->and($book->contributors->first()->contributorRole->name)->toBe('Muharrir');
});

it('rejects a contributor row with a role but no name', function () {
    $role = ContributorRole::factory()->create();

    $this->from(route('admin.books.create'))
        ->post(route('admin.books.store'), [
            'title' => 'X',
            'contributors' => [
                ['contributor_role_id' => $role->id, 'name' => ''],
            ],
        ])
        ->assertSessionHasErrors('contributors.0.name');
});

it('saves a dissertation with no author and two contributors', function () {
    $issue = JournalIssue::factory()->create();
    $roleA = ContributorRole::factory()->create();
    $roleB = ContributorRole::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Muallifsiz dissertatsiya',
        'contributors' => [
            ['contributor_role_id' => $roleA->id, 'name' => 'To‘plovchi T.'],
            ['contributor_role_id' => $roleB->id, 'name' => 'Tarjimon J.'],
        ],
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Muallifsiz dissertatsiya');
    expect($dissertation)->not->toBeNull()
        ->and($dissertation->author)->toBeNull()
        ->and($dissertation->contributors)->toHaveCount(2);
});

it('shows previously saved contributors on the book edit form', function () {
    $book = Book::factory()->create();
    $role = ContributorRole::factory()->create(['name' => 'Rassom']);
    $book->contributors()->create(['contributor_role_id' => $role->id, 'name' => 'Chizuvchi Ch.']);

    $this->get(route('admin.books.edit', $book))
        ->assertSee('Chizuvchi Ch.')
        ->assertSee('Rassom');
});
