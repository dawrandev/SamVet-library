<?php

it('sets baseline security headers on every web response', function () {
    $res = $this->get(route('home'));

    $res->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
});

it('does not set Strict-Transport-Security outside production', function () {
    $res = $this->get(route('home'));

    $res->assertHeaderMissing('Strict-Transport-Security');
});

it('sets Strict-Transport-Security for a secure request in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // Symfony's Request::create() derives the secure/HTTPS flag from the
    // URL's own scheme (overriding withServerVariables(['HTTPS' => 'on'])
    // when the URL itself is http://) — an explicit https:// URL is the only
    // reliable way to simulate a secure request here.
    $httpsUrl = str_replace('http://', 'https://', route('home'));
    $res = $this->get($httpsUrl);

    $res->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('does not set Strict-Transport-Security in production over plain HTTP', function () {
    app()->detectEnvironment(fn () => 'production');

    $res = $this->get(route('home'));

    $res->assertHeaderMissing('Strict-Transport-Security');
});
