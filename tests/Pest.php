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
