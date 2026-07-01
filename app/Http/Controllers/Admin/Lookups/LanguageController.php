<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\Language;
use App\Services\Lookups\LanguageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function __construct(
        private readonly LanguageService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.languages.index', [
            'languages' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.languages.index')
            ->with('success', __('Til qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, Language $language): RedirectResponse
    {
        $this->service->update($language, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.languages.index')
            ->with('success', __('Til yangilandi.'));
    }

    public function destroy(Language $language): RedirectResponse
    {
        $this->service->delete($language);

        return redirect()
            ->route('admin.lookups.languages.index')
            ->with('success', __('Til o‘chirildi.'));
    }
}
