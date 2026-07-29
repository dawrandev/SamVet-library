<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Same limits as the reader-login throttle (ReaderAuthService). */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * Display the login form.
     */
    public function show(): View
    {
        return view('pages.auth.login');
    }

    /**
     * Handle the login request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ], [], [
            'username' => 'Login',
            'password' => 'Parol',
        ]);

        $key = $this->throttleKey($credentials['username'], $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => __('Juda ko‘p urinish. :n soniyadan so‘ng qayta urinib ko‘ring.', [
                    'n' => RateLimiter::availableIn($key),
                ])]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Login yoki parol noto‘g‘ri.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function throttleKey(string $username, string $ip): string
    {
        return 'admin-login|'.Str::lower($username).'|'.$ip;
    }

    /**
     * Log out of the system.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
