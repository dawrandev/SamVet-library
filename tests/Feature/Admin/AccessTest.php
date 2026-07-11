<?php

it('redirects a guest from the admin panel to the admin login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('keeps guests out of an admin resource list', function () {
    $this->get(route('admin.books.index'))->assertRedirect(route('login'));
    $this->get(route('admin.dissertations.index'))->assertRedirect(route('login'));
});

it('lets an authenticated user into the admin panel', function () {
    actingAsAdmin();

    $this->get('/admin')->assertOk();
});
