<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\Region;
use App\Services\Lookups\RegionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function __construct(
        private readonly RegionService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.regions.index', [
            'regions' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.regions.index')
            ->with('success', __('Viloyat qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, Region $region): RedirectResponse
    {
        $this->service->update($region, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.regions.index')
            ->with('success', __('Viloyat yangilandi.'));
    }

    public function destroy(Region $region): RedirectResponse
    {
        $this->service->delete($region);

        return redirect()
            ->route('admin.lookups.regions.index')
            ->with('success', __('Viloyat o‘chirildi.'));
    }
}
