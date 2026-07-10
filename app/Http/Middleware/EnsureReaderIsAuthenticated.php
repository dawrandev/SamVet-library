<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the reading endpoints. The framework's `auth` middleware would send
 * guests to the admin login page, so readers get their own redirect target.
 */
class EnsureReaderIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $reader = Auth::guard('reader')->user();

        if ($reader === null) {
            return redirect()
                ->guest(route('reader.login'))
                ->with('status', __('Materialni o‘qish uchun tizimga kiring.'));
        }

        // A reader blocked after signing in must not keep reading.
        if (! $reader->canSignIn()) {
            Auth::guard('reader')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('reader.login')
                ->with('status', __('Hisobingiz faol emas. Kutubxonaga murojaat qiling.'));
        }

        return $next($request);
    }
}
