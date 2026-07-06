<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Switch the language (persisted in the session).
     */
    public function switch(string $locale): RedirectResponse
    {
        if (array_key_exists($locale, config('locale.supported'))) {
            session()->put('locale', $locale);
        }

        return redirect()->back();
    }
}
