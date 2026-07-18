<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\MasterSpecialty;
use App\Services\Lookups\MasterSpecialtyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterSpecialtyController extends Controller
{
    public function __construct(
        private readonly MasterSpecialtyService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.master-specialties.index', [
            'specialties' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.master-specialties.index')
            ->with('success', __('Mutaxassislik qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, MasterSpecialty $masterSpecialty): RedirectResponse
    {
        $this->service->update($masterSpecialty, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.master-specialties.index')
            ->with('success', __('Mutaxassislik yangilandi.'));
    }

    public function destroy(MasterSpecialty $masterSpecialty): RedirectResponse
    {
        $this->service->delete($masterSpecialty);

        return redirect()
            ->route('admin.lookups.master-specialties.index')
            ->with('success', __('Mutaxassislik o‘chirildi.'));
    }
}
