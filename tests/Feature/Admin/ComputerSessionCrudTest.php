<?php

use App\Models\Computer;
use App\Models\ComputerSession;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('creates a session with auto-captured issued_at and a computed expires_at', function () {
    $reader = Reader::factory()->create();
    $computer = Computer::factory()->create(['location' => 'reading_hall']);

    $this->post(route('admin.readers.computer-sessions.store', $reader), [
        'computer_id' => $computer->id,
        'duration_minutes' => 45,
    ])->assertRedirect();

    $session = ComputerSession::firstWhere('reader_id', $reader->id);
    expect($session)->not->toBeNull()
        ->and($session->issued_at->diffInSeconds(now()))->toBeLessThan(5)
        ->and((int) abs($session->expires_at->diffInMinutes($session->issued_at)))->toBe(45)
        ->and($session->duration_minutes)->toBe(45);
});

it('requires a computer_id (no more free-text fallback)', function () {
    $reader = Reader::factory()->create();

    $this->from(route('admin.readers.show', $reader))
        ->post(route('admin.readers.computer-sessions.store', $reader), [
            'duration_minutes' => 30,
        ])
        ->assertSessionHasErrors('computer_id');
});

it('derives location server-side — a submitted location value has no effect', function () {
    $reader = Reader::factory()->create();
    $computer = Computer::factory()->create(['location' => 'electronic_library_hall']);

    $this->post(route('admin.readers.computer-sessions.store', $reader), [
        'computer_id' => $computer->id,
        'duration_minutes' => 30,
        'location' => 'book_lending', // not a real form field — DTO has no such property
    ])->assertRedirect();

    $session = ComputerSession::firstWhere('reader_id', $reader->id);
    expect($session->location->value)->toBe('electronic_library_hall');
});

it('finishes a session and sets returned_at', function () {
    $session = ComputerSession::factory()->create();

    $this->patch(route('admin.computer-sessions.finish', $session))->assertRedirect();

    $session->refresh();
    expect($session->isFinished())->toBeTrue()
        ->and($session->returned_at->diffInSeconds(now()))->toBeLessThan(5);
});

it('guards against double-finish (returned_at does not move on a second click)', function () {
    $session = ComputerSession::factory()->create();

    $this->patch(route('admin.computer-sessions.finish', $session));
    $session->refresh();
    $firstReturnedAt = $session->returned_at;

    $this->travel(1)->minutes();
    $this->patch(route('admin.computer-sessions.finish', $session));

    $session->refresh();
    expect($session->returned_at->equalTo($firstReturnedAt))->toBeTrue();
});

it('extends an active session from its current expiry (remaining time is not lost)', function () {
    $session = ComputerSession::factory()->create();
    $originalExpiry = $session->expires_at->clone();

    $this->patch(route('admin.computer-sessions.extend', $session), ['minutes' => 15])->assertRedirect();

    $session->refresh();
    expect((int) abs($session->expires_at->diffInMinutes($originalExpiry)))->toBe(15);
});

it('extends an already-expired session from now, not from the stale expiry', function () {
    $session = ComputerSession::factory()->expired()->create();

    $this->patch(route('admin.computer-sessions.extend', $session), ['minutes' => 10])->assertRedirect();

    $session->refresh();
    expect($session->expires_at->isFuture())->toBeTrue()
        ->and($session->expires_at->diffInMinutes(now()))->toBeLessThanOrEqual(10);
});

it('cannot extend a finished session', function () {
    $session = ComputerSession::factory()->finished()->create();
    $expiryBefore = $session->expires_at->clone();

    $this->patch(route('admin.computer-sessions.extend', $session), ['minutes' => 15]);

    $session->refresh();
    expect($session->expires_at->equalTo($expiryBefore))->toBeTrue();
});

it('reports isExpired/isActive/isFinished correctly', function () {
    $active = ComputerSession::factory()->create();
    $expired = ComputerSession::factory()->expired()->create();
    $finished = ComputerSession::factory()->finished()->create();

    expect($active->isActive())->toBeTrue()->and($active->isExpired())->toBeFalse()->and($active->isFinished())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()->and($expired->isActive())->toBeFalse()
        ->and($finished->isFinished())->toBeTrue()->and($finished->isExpired())->toBeFalse()->and($finished->isActive())->toBeFalse();
});

it('filters the cross-reader index page by scope', function () {
    $active = ComputerSession::factory()->create();
    $expired = ComputerSession::factory()->expired()->create();
    $finished = ComputerSession::factory()->finished()->create();

    $this->get(route('admin.computer-sessions.index', ['scope' => 'active']))
        ->assertSee($active->reader->full_name)
        ->assertDontSee($expired->reader->full_name)
        ->assertDontSee($finished->reader->full_name);

    $this->get(route('admin.computer-sessions.index', ['scope' => 'expired']))
        ->assertSee($expired->reader->full_name)
        ->assertDontSee($active->reader->full_name);

    $this->get(route('admin.computer-sessions.index', ['scope' => 'finished']))
        ->assertSee($finished->reader->full_name)
        ->assertDontSee($active->reader->full_name);
});

it('renders the computer_number-based checkout picker, not inventory_number', function () {
    $reader = Reader::factory()->create();
    Computer::factory()->create(['computer_number' => '3', 'inventory_number' => 'KMP-SECRET-INV', 'model' => 'HP EliteDesk']);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('3 — HP EliteDesk')
        ->assertDontSee('KMP-SECRET-INV');
});

it('excludes computers with no computer_number from the checkout picker', function () {
    $reader = Reader::factory()->create();
    Computer::factory()->create(['computer_number' => null, 'model' => 'Unassigned Machine']);

    $this->get(route('admin.readers.show', $reader))
        ->assertDontSee('Unassigned Machine');
});
