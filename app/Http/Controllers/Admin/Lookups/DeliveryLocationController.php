<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\DeliveryLocation;
use App\Services\Lookups\DeliveryLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeliveryLocationController extends Controller
{
    public function __construct(
        private readonly DeliveryLocationService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.delivery-locations.index', [
            'locations' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.delivery-locations.index')
            ->with('success', __('Manzil qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->service->update($deliveryLocation, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.delivery-locations.index')
            ->with('success', __('Manzil yangilandi.'));
    }

    public function destroy(DeliveryLocation $deliveryLocation): RedirectResponse
    {
        $this->service->delete($deliveryLocation);

        return redirect()
            ->route('admin.lookups.delivery-locations.index')
            ->with('success', __('Manzil o‘chirildi.'));
    }
}
