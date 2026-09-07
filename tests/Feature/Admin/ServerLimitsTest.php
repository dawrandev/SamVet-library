<?php

use App\Services\ServerLimitsService;

beforeEach(fn () => actingAsAdmin());

it('shows the effective PHP limits to an admin', function () {
    $this->get(route('admin.server-limits.index'))
        ->assertOk()
        ->assertSee('upload_max_filesize')
        ->assertSee('post_max_size')
        ->assertSee('memory_limit');
});

it('is not reachable without signing in', function () {
    auth('web')->logout();

    $this->get(route('admin.server-limits.index'))->assertRedirect();
});

it('converts PHP shorthand sizes to bytes', function (string $input, int $expected) {
    expect(ServerLimitsService::toBytes($input))->toBe($expected);
})->with([
    ['128M', 134217728],
    ['2G', 2147483648],
    ['512K', 524288],
    ['1024', 1024],
    ['-1', -1],
]);

it('takes the smallest real limit and ignores unlimited ones', function (array $limits, int $expected) {
    expect(ServerLimitsService::effectiveLimit($limits))->toBe($expected);
})->with([
    'smallest wins' => [[100, 50, 200], 50],
    'unlimited is not a ceiling' => [[-1, 50], 50],
    'unlimited on either side' => [[50, -1], 50],
    'all unlimited stays unlimited' => [[-1, -1], -1],
    'zero is not a real ceiling either' => [[0, 80], 80],
]);

it('flags a validation rule that promises more than the server accepts', function () {
    $verdict = app(ServerLimitsService::class)->verdict();

    // Whatever this environment's limits are, the mismatch flag must agree
    // with the two numbers it is derived from.
    $expected = $verdict['effective_upload'] > 0
        && $verdict['app_allows'] > $verdict['effective_upload'];

    expect($verdict['mismatch'])->toBe($expected);
});
