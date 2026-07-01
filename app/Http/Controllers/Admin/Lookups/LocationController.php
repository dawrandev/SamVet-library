<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\Location;
use App\Services\Lookups\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.locations.index', [
            'locations' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.locations.index')
            ->with('success', __('Joylashuv qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, Location $location): RedirectResponse
    {
        $this->service->update($location, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.locations.index')
            ->with('success', __('Joylashuv yangilandi.'));
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->service->delete($location);

        return redirect()
            ->route('admin.lookups.locations.index')
            ->with('success', __('Joylashuv o‘chirildi.'));
    }
}
