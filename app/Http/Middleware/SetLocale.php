<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Sessiyadagi tanlangan tilni o'rnatadi (default — config app.locale).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Admin panel doim o'zbekcha; ko'p tillilik faqat client sayt uchun.
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
