<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Tilni almashtirish (sessiyaga saqlanadi).
     */
    public function switch(string $locale): RedirectResponse
    {
        if (array_key_exists($locale, config('locale.supported'))) {
            session()->put('locale', $locale);
        }

        return redirect()->back();
    }
}
