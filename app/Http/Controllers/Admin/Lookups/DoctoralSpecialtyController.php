<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\DoctoralSpecialty;
use App\Services\Lookups\DoctoralSpecialtyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DoctoralSpecialtyController extends Controller
{
    public function __construct(
        private readonly DoctoralSpecialtyService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.doctoral-specialties.index', [
            'specialties' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.doctoral-specialties.index')
            ->with('success', __('Ixtisoslik qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, DoctoralSpecialty $doctoralSpecialty): RedirectResponse
    {
        $this->service->update($doctoralSpecialty, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.doctoral-specialties.index')
            ->with('success', __('Ixtisoslik yangilandi.'));
    }

    public function destroy(DoctoralSpecialty $doctoralSpecialty): RedirectResponse
    {
        $this->service->delete($doctoralSpecialty);

        return redirect()
            ->route('admin.lookups.doctoral-specialties.index')
            ->with('success', __('Ixtisoslik o‘chirildi.'));
    }
}
