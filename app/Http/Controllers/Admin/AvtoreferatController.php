<?php

namespace App\Http\Controllers\Admin;

use App\Data\AvtoreferatData;
use App\Exports\AvtoreferatsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvtoreferatRequest;
use App\Models\Avtoreferat;
use App\Services\AvtoreferatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvtoreferatController extends Controller
{
    public function __construct(
        private readonly AvtoreferatService $avtoreferatService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.admin.avtoreferats.index', [
            'avtoreferats' => $this->avtoreferatService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = array_filter($request->only(['search']), fn ($v) => $v !== null && $v !== '');

        return Excel::download(new AvtoreferatsExport($filters), 'avtoreferatlar-'.now()->format('Y-m-d').'.xlsx');
    }

    public function create(): View
    {
        return view('pages.admin.avtoreferats.create', [
            ...$this->avtoreferatService->formOptions(),
        ]);
    }

    public function store(AvtoreferatRequest $request): RedirectResponse
    {
        $avtoreferat = $this->avtoreferatService->create(AvtoreferatData::fromRequest($request));

        return redirect()
            ->route('admin.avtoreferats.show', $avtoreferat)
            ->with('success', __('Avtoreferat yaratildi.'));
    }

    public function show(Avtoreferat $avtoreferat): View
    {
        $avtoreferat->load('publicationPlace', 'languages');

        return view('pages.admin.avtoreferats.show', ['avtoreferat' => $avtoreferat]);
    }

    public function edit(Avtoreferat $avtoreferat): View
    {
        return view('pages.admin.avtoreferats.edit', [
            'avtoreferat' => $avtoreferat,
            ...$this->avtoreferatService->formOptions(),
        ]);
    }

    public function update(AvtoreferatRequest $request, Avtoreferat $avtoreferat): RedirectResponse
    {
        $this->avtoreferatService->update($avtoreferat, AvtoreferatData::fromRequest($request));

        return redirect()
            ->route('admin.avtoreferats.show', $avtoreferat)
            ->with('success', __('Avtoreferat yangilandi.'));
    }

    public function destroy(Avtoreferat $avtoreferat): RedirectResponse
    {
        $this->avtoreferatService->delete($avtoreferat);

        return redirect()
            ->route('admin.avtoreferats.index')
            ->with('success', __('Avtoreferat o‘chirildi.'));
    }
}
