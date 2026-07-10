<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ReaderLoginRequest;
use App\Services\Site\ReaderAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReaderAuthController extends Controller
{
    public function __construct(
        private readonly ReaderAuthService $readerAuth,
    ) {}

    /** Sign-in form. */
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('reader')->check()) {
            return redirect()->route('home');
        }

        return view('pages.site.auth.login');
    }

    /** Sign the reader in and send them where they were headed. */
    public function store(ReaderLoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->readerAuth->login($data['id_number'], $data['password'], (string) $request->ip());

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->readerAuth->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
