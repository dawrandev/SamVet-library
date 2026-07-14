<?php

namespace App\Http\Controllers\Admin;

use App\Data\JournalData;
use App\Enums\PublicationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalRequest;
use App\Http\Requests\Admin\UpdateJournalRequest;
use App\Models\Journal;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(
        private readonly JournalService $journalService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'journal_type_id', 'kind']);

        return view('pages.admin.journals.index', [
            'journals' => $this->journalService->paginate($filters),
            'filters' => $filters,
            ...$this->journalService->filterOptions(),
        ]);
    }

    public function show(Journal $journal): View
    {
        $journal->load(['type', 'language', 'publicationPlace', 'issues' => fn ($q) => $q->withCount('copies')]);

        return view('pages.admin.journals.show', ['journal' => $journal]);
    }

    public function create(Request $request): View
    {
        return view('pages.admin.journals.create', [
            'kind' => $request->string('kind')->toString() ?: null,
            ...$this->journalService->formOptions(),
        ]);
    }

    public function store(StoreJournalRequest $request): RedirectResponse
    {
        $journal = $this->journalService->create(JournalData::fromRequest($request));

        $message = $journal->kind === PublicationKind::Newspaper
            ? __('Gazeta yaratildi. Endi sonlar qo‘shishingiz mumkin.')
            : __('Jurnal yaratildi. Endi sonlar qo‘shishingiz mumkin.');

        return redirect()
            ->route('admin.journals.show', $journal)
            ->with('success', $message);
    }

    public function edit(Journal $journal): View
    {
        return view('pages.admin.journals.edit', [
            'journal' => $journal,
            ...$this->journalService->formOptions(),
        ]);
    }

    public function update(UpdateJournalRequest $request, Journal $journal): RedirectResponse
    {
        $this->journalService->update($journal, JournalData::fromRequest($request));

        $message = $journal->kind === PublicationKind::Newspaper
            ? __('Gazeta yangilandi.')
            : __('Jurnal yangilandi.');

        return redirect()
            ->route('admin.journals.index', ['kind' => $journal->kind?->value])
            ->with('success', $message);
    }

    public function destroy(Journal $journal): RedirectResponse
    {
        $kind = $journal->kind;

        $this->journalService->delete($journal);

        $message = $kind === PublicationKind::Newspaper
            ? __('Gazeta o‘chirildi.')
            : __('Jurnal o‘chirildi.');

        return redirect()
            ->route('admin.journals.index', ['kind' => $kind?->value])
            ->with('success', $message);
    }
}
