<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\AffiliationGroup;
use App\Services\Lookups\AffiliationGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliationGroupController extends Controller
{
    public function __construct(
        private readonly AffiliationGroupService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.affiliation-groups.index', [
            'affiliationGroups' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-groups.index')
            ->with('success', __('Guruh/lavozim qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, AffiliationGroup $affiliationGroup): RedirectResponse
    {
        $this->service->update($affiliationGroup, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.affiliation-groups.index')
            ->with('success', __('Guruh/lavozim yangilandi.'));
    }

    public function destroy(AffiliationGroup $affiliationGroup): RedirectResponse
    {
        $this->service->delete($affiliationGroup);

        return redirect()
            ->route('admin.lookups.affiliation-groups.index')
            ->with('success', __('Guruh/lavozim o‘chirildi.'));
    }
}
