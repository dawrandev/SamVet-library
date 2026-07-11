<?php

namespace App\Http\Controllers\Admin;

use App\Data\AvtoreferatData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvtoreferatRequest;
use App\Models\Avtoreferat;
use App\Services\AvtoreferatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvtoreferatController extends Controller
{
    public function __construct(
        private readonly AvtoreferatService $avtoreferatService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'journal_id', 'resource_field_id']);

        return view('pages.admin.avtoreferats.index', [
            'avtoreferats' => $this->avtoreferatService->paginate($filters),
            'filters' => $filters,
            ...$this->avtoreferatService->filterOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        // After a validation error, re-select the journal/issue the user had chosen.
        $selection = $this->avtoreferatService->formSelection(
            $this->intOldInput($request, 'journal_id'),
            $this->intOldInput($request, 'journal_issue_id'),
        );

        return view('pages.admin.avtoreferats.create', [
            ...$this->avtoreferatService->formOptions(),
            ...$selection,
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
        $avtoreferat->load([
            'journalIssue.journal.type',
            'journalIssue.journal.publicationPlace',
            'resourceField',
        ]);

        return view('pages.admin.avtoreferats.show', ['avtoreferat' => $avtoreferat]);
    }

    public function edit(Request $request, Avtoreferat $avtoreferat): View
    {
        $avtoreferat->load('journalIssue.journal.type');

        // Old input (after a validation error) wins over the stored value.
        $selection = $this->avtoreferatService->formSelection(
            $this->intOldInput($request, 'journal_id') ?? $avtoreferat->journalIssue?->journal_id,
            $this->intOldInput($request, 'journal_issue_id') ?? $avtoreferat->journal_issue_id,
        );

        return view('pages.admin.avtoreferats.edit', [
            'avtoreferat' => $avtoreferat,
            ...$this->avtoreferatService->formOptions(),
            ...$selection,
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

    /**
     * Read a flashed old-input value as a positive int, or null when absent.
     */
    private function intOldInput(Request $request, string $key): ?int
    {
        $value = $request->old($key);

        return ($value === null || $value === '') ? null : (int) $value;
    }
}
