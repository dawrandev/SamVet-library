<?php

namespace App\Services\Site;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in for public-site readers: ID number + the library's password.
 * Brute force is limited per ID number and IP.
 */
class ReaderAuthService
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    /**
     * Authenticate the reader and start their session.
     *
     * @throws ValidationException on bad credentials, lockout, or inactive account
     */
    public function login(string $idNumber, string $password, string $ip): void
    {
        $key = $this->throttleKey($idNumber, $ip);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'id_number' => __('Juda ko‘p urinish. :n soniyadan so‘ng qayta urinib ko‘ring.', [
                    'n' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        if (! Auth::guard('reader')->attempt(['id_number' => $idNumber, 'password' => $password])) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'id_number' => __('ID raqam yoki parol noto‘g‘ri.'),
            ]);
        }

        // Credentials were right, but the account may be blocked or closed.
        if (! Auth::guard('reader')->user()->canSignIn()) {
            Auth::guard('reader')->logout();

            throw ValidationException::withMessages([
                'id_number' => __('Hisobingiz faol emas. Kutubxonaga murojaat qiling.'),
            ]);
        }

        RateLimiter::clear($key);
    }

    public function logout(): void
    {
        Auth::guard('reader')->logout();
    }

    private function throttleKey(string $idNumber, string $ip): string
    {
        return 'reader-login|'.Str::lower($idNumber).'|'.$ip;
    }
}
