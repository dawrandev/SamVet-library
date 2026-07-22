<?php

namespace App\Http\Controllers\Admin;

use App\Data\JournalData;
use App\Enums\PublicationKind;
use App\Exports\JournalsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalRequest;
use App\Http\Requests\Admin\UpdateJournalRequest;
use App\Models\Journal;
use App\Services\ArticleService;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JournalController extends Controller
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly ArticleService $articleService,
    ) {}

    /**
     * The Turi selector's "Maqola" branch needs the article form's own lookups
     * (Journal's own formOptions() already covers `languages`, shared by both).
     *
     * @return array<string, mixed>
     */
    private function articleFieldOptions(): array
    {
        $options = $this->articleService->formOptions();

        return [
            'resourceFields' => $options['resourceFields'],
            'contributorRoles' => $options['contributorRoles'],
        ];
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'journal_type_id', 'kind']);

        return view('pages.admin.journals.index', [
            'journals' => $this->journalService->paginate($filters),
            'filters' => $filters,
            ...$this->journalService->filterOptions(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = array_filter($request->only(['search', 'journal_type_id', 'kind']), fn ($v) => $v !== null && $v !== '');

        return Excel::download(new JournalsExport($filters), 'nashrlar-'.now()->format('Y-m-d').'.xlsx');
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
            ...$this->articleFieldOptions(),
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
            ...$this->articleFieldOptions(),
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
