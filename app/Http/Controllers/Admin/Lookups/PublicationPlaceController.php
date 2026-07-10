<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\PublicationPlace;
use App\Services\Lookups\PublicationPlaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicationPlaceController extends Controller
{
    public function __construct(
        private readonly PublicationPlaceService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.publication-places.index', [
            'places' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.publication-places.index')
            ->with('success', __('Nashriyot joyi qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, PublicationPlace $publicationPlace): RedirectResponse
    {
        $this->service->update($publicationPlace, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.publication-places.index')
            ->with('success', __('Nashriyot joyi yangilandi.'));
    }

    public function destroy(PublicationPlace $publicationPlace): RedirectResponse
    {
        $this->service->delete($publicationPlace);

        return redirect()
            ->route('admin.lookups.publication-places.index')
            ->with('success', __('Nashriyot joyi o‘chirildi.'));
    }
}
