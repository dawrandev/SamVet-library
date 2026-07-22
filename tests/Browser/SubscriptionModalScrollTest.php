<?php

use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * The create/edit modal's form is taller than a typical laptop viewport.
 * Without a capped height + its own scroll, the panel (a `fixed inset-0`
 * layer) overflows past the viewport with no way to reach the Saqlash
 * button — it renders, but outside the visible area.
 */
it('keeps the Saqlash button reachable within the viewport on a short screen', function () {
    $admin = User::factory()->create();

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->resize(1280, 650)
            ->loginAs($admin)
            ->visit('/admin/subscriptions')
            ->press('Yangi obuna')
            ->waitFor('#m_journal');

        $rect = $browser->script(<<<'JS'
            const buttons = [...document.querySelectorAll('button[type="submit"]')];
            const btn = buttons.find(b => b.textContent.trim() === 'Saqlash');
            if (!btn) return null;
            // Scrolling the button into view only *reaches* it if some ancestor
            // is actually a scroll container — without the fix, the fixed-position
            // overlay has no scrollable ancestor, so this is a no-op and the
            // button stays off-screen.
            btn.scrollIntoView({ block: 'center' });
            const r = btn.getBoundingClientRect();
            return { top: r.top, bottom: r.bottom, viewportHeight: window.innerHeight };
        JS)[0];

        expect($rect)->not->toBeNull('Saqlash button not found in the DOM');
        expect($rect['bottom'])->toBeLessThanOrEqual($rect['viewportHeight']);
        expect($rect['top'])->toBeGreaterThanOrEqual(0);
    });
});
