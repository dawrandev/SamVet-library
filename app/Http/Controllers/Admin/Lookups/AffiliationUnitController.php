<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\AffiliationUnit;
use App\Services\Lookups\AffiliationUnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliationUnitController extends Controller
{
    public function __construct(
        private readonly AffiliationUnitService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.affiliation-units.index', [
            'affiliationUnits' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-units.index')
            ->with('success', __('Mutaxassislik/bo‘lim qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, AffiliationUnit $affiliationUnit): RedirectResponse
    {
        $this->service->update($affiliationUnit, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-units.index')
            ->with('success', __('Mutaxassislik/bo‘lim yangilandi.'));
    }

    public function destroy(AffiliationUnit $affiliationUnit): RedirectResponse
    {
        $this->service->delete($affiliationUnit);

        return redirect()
            ->route('admin.lookups.affiliation-units.index')
            ->with('success', __('Mutaxassislik/bo‘lim o‘chirildi.'));
    }
}
