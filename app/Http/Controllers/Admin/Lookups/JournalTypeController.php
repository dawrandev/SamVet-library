<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\JournalType;
use App\Services\Lookups\JournalTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JournalTypeController extends Controller
{
    public function __construct(
        private readonly JournalTypeService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.journal-types.index', [
            'journalTypes' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.journal-types.index')
            ->with('success', __('Jurnal turi qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, JournalType $journalType): RedirectResponse
    {
        $this->service->update($journalType, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.journal-types.index')
            ->with('success', __('Jurnal turi yangilandi.'));
    }

    public function destroy(JournalType $journalType): RedirectResponse
    {
        $this->service->delete($journalType);

        return redirect()
            ->route('admin.lookups.journal-types.index')
            ->with('success', __('Jurnal turi o‘chirildi.'));
    }
}
