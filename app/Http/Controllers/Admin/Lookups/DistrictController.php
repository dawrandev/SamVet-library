<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\DistrictLookupRequest;
use App\Models\District;
use App\Services\Lookups\DistrictService;
use App\Services\Lookups\RegionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function __construct(
        private readonly DistrictService $service,
        private readonly RegionService $regionService,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.districts.index', [
            'districts' => $this->service->list(),
            'regions' => $this->regionService->list(),
        ]);
    }

    public function store(DistrictLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.districts.index')
            ->with('success', __('Tuman qo‘shildi.'));
    }

    public function update(DistrictLookupRequest $request, District $district): RedirectResponse
    {
        $this->service->update($district, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.districts.index')
            ->with('success', __('Tuman yangilandi.'));
    }

    public function destroy(District $district): RedirectResponse
    {
        $this->service->delete($district);

        return redirect()
            ->route('admin.lookups.districts.index')
            ->with('success', __('Tuman o‘chirildi.'));
    }
}
