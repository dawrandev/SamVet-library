<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Sets the language selected in the session (default — config app.locale).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The admin panel is always in Uzbek; multilingualism is only for the client site.
        if ($request->is('admin', 'admin/*')) {
            app()->setLocale('uz');

            return $next($request);
        }

        $locale = $request->session()->get('locale', config('app.locale'));

        if (array_key_exists($locale, config('locale.supported'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
