<?php

use App\Enums\LoanStatus;
use App\Models\Loan;

beforeEach(fn () => actingAsAdmin());

it('excludes returned loans from the default (overdue) scope', function () {
    $onLoan = Loan::factory()->create(['due_at' => now()->subDays(3)]);
    $returned = Loan::factory()->create([
        'status' => LoanStatus::Returned->value,
        'returned_at' => now()->subDay(),
        'due_at' => now()->subDays(3),
    ]);

    $res = $this->get(route('admin.loans.index'));

    $ids = $res->viewData('loans')->pluck('id');
    expect($ids)->toContain($onLoan->id)
        ->not->toContain($returned->id);
});

it('scope=returned shows only returned loans, most recently returned first', function () {
    $onLoan = Loan::factory()->create();
    $olderReturn = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => now()->subDays(5)]);
    $recentReturn = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => now()->subDay()]);

    $res = $this->get(route('admin.loans.index', ['scope' => 'returned']));

    $ids = $res->viewData('loans')->pluck('id')->all();
    expect($ids)->not->toContain($onLoan->id)
        ->and($ids)->toBe([$recentReturn->id, $olderReturn->id]);
});

it('scope=all shows both on-loan and returned loans together', function () {
    $onLoan = Loan::factory()->create();
    $returned = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => now()->subDay()]);

    $res = $this->get(route('admin.loans.index', ['scope' => 'all']));

    $ids = $res->viewData('loans')->pluck('id');
    expect($ids)->toContain($onLoan->id, $returned->id);
});

it('filters returned loans by a returned_at date range', function () {
    $inRange = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => '2026-06-15']);
    $beforeRange = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => '2026-05-01']);

    $res = $this->get(route('admin.loans.index', [
        'scope' => 'returned',
        'returned_from' => '2026-06-01',
        'returned_to' => '2026-06-30',
    ]));

    $ids = $res->viewData('loans')->pluck('id');
    expect($ids)->toContain($inRange->id)
        ->not->toContain($beforeRange->id);
});

it('shows the "Qaytardi" action only for a still-on-loan row, not an already-returned one', function () {
    $onLoan = Loan::factory()->create();
    $returned = Loan::factory()->create(['status' => LoanStatus::Returned->value, 'returned_at' => now()]);

    $res = $this->get(route('admin.loans.index', ['scope' => 'all']));

    $res->assertSee(route('admin.loans.return', $onLoan), false)
        ->assertDontSee(route('admin.loans.return', $returned), false);
});
