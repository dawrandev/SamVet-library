<?php

namespace App\Http\Controllers\Admin;

use App\Data\SubscriptionCatalogData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionCatalogRequest;
use App\Http\Requests\Admin\UpdateSubscriptionCatalogRequest;
use App\Models\Journal;
use App\Models\SubscriptionCatalog;
use App\Services\SubscriptionCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionCatalogController extends Controller
{
    public function __construct(
        private readonly SubscriptionCatalogService $catalogService,
    ) {}

    public function index(Request $request): View
    {
        $year = $request->integer('year') ?: (int) date('Y');

        return view('pages.admin.subscription-catalog.index', [
            'year' => $year,
            'years' => $this->catalogService->years(),
            'entries' => $this->catalogService->forYear($year),
            'journals' => Journal::orderBy('name')->get(['id', 'name', 'index']),
        ]);
    }

    public function store(StoreSubscriptionCatalogRequest $request): RedirectResponse
    {
        $data = SubscriptionCatalogData::fromRequest($request);
        $this->catalogService->create($data);

        return redirect()
            ->route('admin.subscription-catalog.index', ['year' => $data->year])
            ->with('success', __('Katalogga qo‘shildi.'));
    }

    public function update(UpdateSubscriptionCatalogRequest $request, SubscriptionCatalog $subscriptionCatalog): RedirectResponse
    {
        $data = SubscriptionCatalogData::fromRequest($request);
        $this->catalogService->update($subscriptionCatalog, $data);

        return redirect()
            ->route('admin.subscription-catalog.index', ['year' => $data->year])
            ->with('success', __('Katalog yangilandi.'));
    }

    public function destroy(SubscriptionCatalog $subscriptionCatalog): RedirectResponse
    {
        $year = $subscriptionCatalog->year;
        $this->catalogService->delete($subscriptionCatalog);

        return redirect()
            ->route('admin.subscription-catalog.index', ['year' => $year])
            ->with('success', __('Katalogdan o‘chirildi.'));
    }
}
