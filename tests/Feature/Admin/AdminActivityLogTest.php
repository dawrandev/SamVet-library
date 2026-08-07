<?php

use App\Enums\AdminActivityAction;
use App\Models\AdminActivityLog;
use App\Models\Book;
use App\Models\Reader;

it('logs a create, update, and delete for a tracked model', function () {
    $admin = actingAsAdmin();

    $book = Book::factory()->create(['title' => 'Original sarlavha']);
    expect(AdminActivityLog::where('subject_type', 'Book')->where('subject_id', $book->id)->where('action', AdminActivityAction::Created)->exists())->toBeTrue();

    $book->update(['title' => 'Yangi sarlavha']);
    $updated = AdminActivityLog::where('subject_type', 'Book')->where('subject_id', $book->id)->where('action', AdminActivityAction::Updated)->first();
    expect($updated)->not->toBeNull()
        ->and($updated->admin_id)->toBe($admin->id)
        ->and($updated->changes['title'])->toBe(['Original sarlavha', 'Yangi sarlavha']);

    $bookId = $book->id;
    $book->delete();
    expect(AdminActivityLog::where('subject_type', 'Book')->where('subject_id', $bookId)->where('action', AdminActivityAction::Deleted)->exists())->toBeTrue();
});

it('does not log a no-op update where nothing meaningful changed', function () {
    actingAsAdmin();
    $book = Book::factory()->create();

    $countAfterCreate = AdminActivityLog::count();
    $book->touch(); // only bumps updated_at — nothing worth logging

    expect(AdminActivityLog::count())->toBe($countAfterCreate);
});

it('does not log anything outside a signed-in admin session', function () {
    Book::factory()->create();

    expect(AdminActivityLog::count())->toBe(0);
});

it('redacts a reader password change instead of storing it in plain/hashed form', function () {
    actingAsAdmin();
    $reader = Reader::factory()->create();

    // `password` is deliberately not mass-assignable (see Reader::$fillable) —
    // set it directly, as the real password-change flow would.
    $reader->password = 'a-new-plaintext-password';
    $reader->save();

    $log = AdminActivityLog::where('subject_type', 'Reader')->where('subject_id', $reader->id)->where('action', AdminActivityAction::Updated)->first();
    expect($log)->not->toBeNull()
        ->and($log->changes['password'])->toBe(['[hidden]', '[hidden]']);
});

it('is reachable directly by URL but has no sidebar link', function () {
    actingAsAdmin();

    // The page itself still works and shows its own heading...
    $this->get(route('admin.activity-log.index'))->assertOk()->assertSee('Faoliyat jurnali');

    // ...but no other admin page links to it from the sidebar (librarian shouldn't
    // stumble onto it — only reachable if you already know the URL).
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Faoliyat jurnali');
});

it('shows the activity log page with filters', function () {
    actingAsAdmin();
    $book = Book::factory()->create();

    $res = $this->get(route('admin.activity-log.index'));
    $res->assertOk()->assertSee('Faoliyat jurnali');

    $res = $this->get(route('admin.activity-log.index', ['subject_type' => 'Book', 'action' => 'created']));
    $res->assertOk()->assertSee('#'.$book->id);

    $res = $this->get(route('admin.activity-log.index', ['subject_type' => 'Reader']));
    $res->assertOk()->assertDontSee('#'.$book->id);
});

it('redirects a guest away from the activity log page', function () {
    $this->get(route('admin.activity-log.index'))->assertRedirect(route('login'));
});

it('does not mistake a signed-in reader for an admin when a tracked model is written', function () {
    // Regression guard: actingAs($reader, 'reader') changes Laravel's *default*
    // auth guard for the rest of the test (Auth::shouldUse()) — a guard-less
    // auth()->check()/auth()->id() in the Observer would have picked up the
    // reader's own id and tried to store it as admin_id, a foreign key
    // referencing `users`, blowing up with an integrity constraint violation.
    // AdminActivityLogService::logChange() must check the `web` guard
    // explicitly for this to behave correctly.
    $reader = actingAsReader();

    $book = Book::factory()->create();

    expect(AdminActivityLog::where('subject_type', 'Book')->where('subject_id', $book->id)->exists())
        ->toBeFalse('no admin is signed in — nothing should be logged, and it must not crash')
        ->and($reader->id)->not->toBe(0); // sanity: a reader really was signed in
});
