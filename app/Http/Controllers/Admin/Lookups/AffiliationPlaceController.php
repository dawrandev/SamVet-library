<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\AffiliationPlace;
use App\Services\Lookups\AffiliationPlaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliationPlaceController extends Controller
{
    public function __construct(
        private readonly AffiliationPlaceService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.affiliation-places.index', [
            'affiliationPlaces' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-places.index')
            ->with('success', __('Joy qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, AffiliationPlace $affiliationPlace): RedirectResponse
    {
        $this->service->update($affiliationPlace, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-places.index')
            ->with('success', __('Joy yangilandi.'));
    }

    public function destroy(AffiliationPlace $affiliationPlace): RedirectResponse
    {
        $this->service->delete($affiliationPlace);

        return redirect()
            ->route('admin.lookups.affiliation-places.index')
            ->with('success', __('Joy o‘chirildi.'));
    }
}
