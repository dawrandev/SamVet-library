<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\Publisher;
use App\Services\Lookups\PublisherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublisherController extends Controller
{
    public function __construct(
        private readonly PublisherService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.publishers.index', [
            'publishers' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.publishers.index')
            ->with('success', __('Nashriyot qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, Publisher $publisher): RedirectResponse
    {
        $this->service->update($publisher, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.publishers.index')
            ->with('success', __('Nashriyot yangilandi.'));
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        $this->service->delete($publisher);

        return redirect()
            ->route('admin.lookups.publishers.index')
            ->with('success', __('Nashriyot o‘chirildi.'));
    }
}
