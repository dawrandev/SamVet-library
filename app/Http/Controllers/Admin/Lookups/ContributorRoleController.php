<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\ContributorRole;
use App\Services\Lookups\ContributorRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContributorRoleController extends Controller
{
    public function __construct(
        private readonly ContributorRoleService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.contributor-roles.index', [
            'roles' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.contributor-roles.index')
            ->with('success', __('Rol qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, ContributorRole $contributorRole): RedirectResponse
    {
        $this->service->update($contributorRole, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.contributor-roles.index')
            ->with('success', __('Rol yangilandi.'));
    }

    public function destroy(ContributorRole $contributorRole): RedirectResponse
    {
        $this->service->delete($contributorRole);

        return redirect()
            ->route('admin.lookups.contributor-roles.index')
            ->with('success', __('Rol o‘chirildi.'));
    }
}
