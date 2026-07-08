<?php

namespace App\Http\Controllers\Admin;

use App\Data\ComputerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreComputerRequest;
use App\Http\Requests\Admin\UpdateComputerRequest;
use App\Models\Computer;
use App\Services\ComputerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComputerController extends Controller
{
    public function __construct(
        private readonly ComputerService $computerService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'status', 'location_id']);

        return view('pages.admin.computers.index', [
            'computers' => $this->computerService->paginate($filters),
            'filters' => $filters,
            ...$this->computerService->formOptions(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.computers.create', $this->computerService->formOptions());
    }

    public function store(StoreComputerRequest $request): RedirectResponse
    {
        $computer = $this->computerService->create(ComputerData::fromRequest($request));

        return redirect()
            ->route('admin.computers.show', $computer)
            ->with('success', __('Kompyuter qo‘shildi.'));
    }

    public function show(Computer $computer): View
    {
        $computer->load('location');

        return view('pages.admin.computers.show', ['computer' => $computer]);
    }

    public function edit(Computer $computer): View
    {
        return view('pages.admin.computers.edit', [
            'computer' => $computer,
            ...$this->computerService->formOptions(),
        ]);
    }

    public function update(UpdateComputerRequest $request, Computer $computer): RedirectResponse
    {
        $this->computerService->update($computer, ComputerData::fromRequest($request));

        return redirect()
            ->route('admin.computers.index')
            ->with('success', __('Kompyuter yangilandi.'));
    }

    public function destroy(Computer $computer): RedirectResponse
    {
        $this->computerService->delete($computer);

        return redirect()
            ->route('admin.computers.index')
            ->with('success', __('Kompyuter o‘chirildi.'));
    }
}
