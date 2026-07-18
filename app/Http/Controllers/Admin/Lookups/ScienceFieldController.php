<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\ScienceField;
use App\Services\Lookups\ScienceFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScienceFieldController extends Controller
{
    public function __construct(
        private readonly ScienceFieldService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.science-fields.index', [
            'fields' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.science-fields.index')
            ->with('success', __('Fan nomi qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, ScienceField $scienceField): RedirectResponse
    {
        $this->service->update($scienceField, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.science-fields.index')
            ->with('success', __('Fan nomi yangilandi.'));
    }

    public function destroy(ScienceField $scienceField): RedirectResponse
    {
        $this->service->delete($scienceField);

        return redirect()
            ->route('admin.lookups.science-fields.index')
            ->with('success', __('Fan nomi o‘chirildi.'));
    }
}
