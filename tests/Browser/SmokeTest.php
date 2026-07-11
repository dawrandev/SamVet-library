<?php

use Laravel\Dusk\Browser;

it('loads the public home page in a real browser', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Elektron katalog')
            ->assertSee('Bosh sahifa');
    });
});
