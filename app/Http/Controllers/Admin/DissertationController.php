<?php

namespace App\Http\Controllers\Admin;

use App\Data\DissertationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DissertationRequest;
use App\Models\Dissertation;
use App\Services\DissertationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DissertationController extends Controller
{
    public function __construct(
        private readonly DissertationService $dissertationService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'journal_id', 'resource_field_id']);

        return view('pages.admin.dissertations.index', [
            'dissertations' => $this->dissertationService->paginate($filters),
            'filters' => $filters,
            ...$this->dissertationService->filterOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        // After a validation error, re-select the journal/issue the user had chosen.
        $selection = $this->dissertationService->formSelection(
            $this->intOldInput($request, 'journal_id'),
            $this->intOldInput($request, 'journal_issue_id'),
        );

        return view('pages.admin.dissertations.create', [
            ...$this->dissertationService->formOptions(),
            ...$selection,
        ]);
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
        $dissertation->load([
            'journalIssue.journal.type',
            'journalIssue.journal.publicationPlace',
            'resourceField',
        ]);

        return view('pages.admin.dissertations.show', ['dissertation' => $dissertation]);
    }

    public function edit(Request $request, Dissertation $dissertation): View
    {
        $dissertation->load('journalIssue.journal.type');

        // Old input (after a validation error) wins over the stored value.
        $selection = $this->dissertationService->formSelection(
            $this->intOldInput($request, 'journal_id') ?? $dissertation->journalIssue?->journal_id,
            $this->intOldInput($request, 'journal_issue_id') ?? $dissertation->journal_issue_id,
        );

        return view('pages.admin.dissertations.edit', [
            'dissertation' => $dissertation,
            ...$this->dissertationService->formOptions(),
            ...$selection,
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

    /**
     * Read a flashed old-input value as a positive int, or null when absent.
     */
    private function intOldInput(Request $request, string $key): ?int
    {
        $value = $request->old($key);

        return ($value === null || $value === '') ? null : (int) $value;
    }
}
