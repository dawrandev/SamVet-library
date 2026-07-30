<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DuskTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature and Unit tests both boot the Laravel app (so __(), config and
| container work). Only Feature tests touch the database — RefreshDatabase
| migrates the dedicated `samvet_test` schema fresh for each test. Browser
| (Dusk) tests run against a live server with their own env.
|
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');

pest()->use(RefreshDatabase::class)->in('Feature');

// FULLTEXT-dependent tests need genuinely committed rows: RefreshDatabase
// wraps every test in a transaction it rolls back, and InnoDB's FULLTEXT
// index isn't reliably visible to MATCH() AGAINST() for a row inserted (but
// never committed) within that same transaction. Truncation instead of
// rollback sidesteps that — see CatalogRepository::applySmartSearch(). Kept
// to its own file (not all of SmartSearchTest.php) since only these two
// tests actually exercise MATCH() — the rest run fine, much faster, under
// the standard RefreshDatabase transaction rollback.
pest()->use(DatabaseTruncation::class)->in('Feature/SmartSearchFulltextTest.php');

// Dusk runs against a live server, so transactions can't roll back — truncate
// the (dedicated) test schema between browser tests instead.
pest()->extend(DuskTestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** Create an admin user and sign in as them (web guard). */
function actingAsAdmin(): \App\Models\User
{
    $user = \App\Models\User::factory()->create();
    test()->actingAs($user);

    return $user;
}

/** Create an active reader and sign in as them (reader guard). */
function actingAsReader(array $attributes = []): \App\Models\Reader
{
    $reader = \App\Models\Reader::factory()->create($attributes);
    test()->actingAs($reader, 'reader');

    return $reader;
}
