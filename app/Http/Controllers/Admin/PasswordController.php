<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Services\Admin\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(
        private readonly AccountService $accounts,
    ) {}

    public function edit(): View
    {
        return view('pages.admin.account.password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->accounts->updatePassword($request->user(), $request->validated()['password']);

        return redirect()->route('admin.password.edit')->with('success', __('Parolingiz muvaffaqiyatli yangilandi.'));
    }
}
