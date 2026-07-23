<?php

namespace App\Http\Controllers\Admin;

use App\Data\DissertationData;
use App\Exports\DissertationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DissertationRequest;
use App\Models\Dissertation;
use App\Services\DissertationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DissertationController extends Controller
{
    public function __construct(
        private readonly DissertationService $dissertationService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'resource_field_id']);

        return view('pages.admin.dissertations.index', [
            'dissertations' => $this->dissertationService->paginate($filters),
            'filters' => $filters,
            ...$this->dissertationService->filterOptions(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = array_filter($request->only(['search', 'resource_field_id']), fn ($v) => $v !== null && $v !== '');

        return Excel::download(new DissertationsExport($filters), 'dissertatsiyalar-'.now()->format('Y-m-d').'.xlsx');
    }

    public function create(): View
    {
        return view('pages.admin.dissertations.create', $this->dissertationService->formOptions());
    }

    public function store(DissertationRequest $request): RedirectResponse
    {
        $dissertation = $this->dissertationService->create(DissertationData::fromRequest($request));

        return redirect()
            ->route('admin.dissertations.show', $dissertation)
            ->with('success', __('Dissertatsiya yaratildi.'));
    }

    public function show(Dissertation $dissertation): View
    {
        $dissertation->load('resourceField');

        return view('pages.admin.dissertations.show', ['dissertation' => $dissertation]);
    }

    public function edit(Dissertation $dissertation): View
    {
        return view('pages.admin.dissertations.edit', [
            'dissertation' => $dissertation,
            ...$this->dissertationService->formOptions(),
        ]);
    }

    public function update(DissertationRequest $request, Dissertation $dissertation): RedirectResponse
    {
        $this->dissertationService->update($dissertation, DissertationData::fromRequest($request));

        return redirect()
            ->route('admin.dissertations.show', $dissertation)
            ->with('success', __('Dissertatsiya yangilandi.'));
    }

    public function destroy(Dissertation $dissertation): RedirectResponse
    {
        $this->dissertationService->delete($dissertation);

        return redirect()
            ->route('admin.dissertations.index')
            ->with('success', __('Dissertatsiya o‘chirildi.'));
    }
}
