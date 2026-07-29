<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\DepartmentCoverageRequest;
use App\Models\DepartmentCoverage;
use App\Services\Lookups\DepartmentCoverageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentCoverageController extends Controller
{
    public function __construct(
        private readonly DepartmentCoverageService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.department-coverages.index', [
            'departmentCoverages' => $this->service->list(),
        ]);
    }

    public function store(DepartmentCoverageRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.department-coverages.index')
            ->with('success', __('Kafedra qo‘shildi.'));
    }

    public function update(DepartmentCoverageRequest $request, DepartmentCoverage $departmentCoverage): RedirectResponse
    {
        $this->service->update($departmentCoverage, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.department-coverages.index')
            ->with('success', __('Kafedra yangilandi.'));
    }

    public function destroy(DepartmentCoverage $departmentCoverage): RedirectResponse
    {
        $this->service->delete($departmentCoverage);

        return redirect()
            ->route('admin.lookups.department-coverages.index')
            ->with('success', __('Kafedra o‘chirildi.'));
    }
}
