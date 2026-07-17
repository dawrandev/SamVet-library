<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\EventLocation;
use App\Services\Lookups\EventLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventLocationController extends Controller
{
    public function __construct(
        private readonly EventLocationService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.event-locations.index', [
            'locations' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.event-locations.index')
            ->with('success', __('Joy qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, EventLocation $eventLocation): RedirectResponse
    {
        $this->service->update($eventLocation, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.event-locations.index')
            ->with('success', __('Joy yangilandi.'));
    }

    public function destroy(EventLocation $eventLocation): RedirectResponse
    {
        $this->service->delete($eventLocation);

        return redirect()
            ->route('admin.lookups.event-locations.index')
            ->with('success', __('Joy o‘chirildi.'));
    }
}
