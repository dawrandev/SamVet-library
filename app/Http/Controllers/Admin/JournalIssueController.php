<?php

namespace App\Http\Controllers\Admin;

use App\Data\JournalIssueData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalIssueRequest;
use App\Http\Requests\Admin\UpdateJournalIssueRequest;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\Location;
use App\Services\JournalIssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JournalIssueController extends Controller
{
    public function __construct(
        private readonly JournalIssueService $issueService,
    ) {}

    public function show(Journal $journal, JournalIssue $issue): View
    {
        $this->ensureIssueBelongsToJournal($journal, $issue);

        $issue->load(['journal', 'copies.location']);

        return view('pages.admin.journals.issues.show', [
            'journal' => $journal,
            'issue' => $issue,
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(StoreJournalIssueRequest $request, Journal $journal): RedirectResponse
    {
        $this->issueService->create($journal, JournalIssueData::fromRequest($request));

        return redirect()
            ->route('admin.journals.show', $journal)
            ->with('success', __('Son qo‘shildi.'));
    }

    public function update(UpdateJournalIssueRequest $request, Journal $journal, JournalIssue $issue): RedirectResponse
    {
        $this->ensureIssueBelongsToJournal($journal, $issue);

        $this->issueService->update($issue, JournalIssueData::fromRequest($request));

        return redirect()
            ->route('admin.journals.show', $journal)
            ->with('success', __('Son yangilandi.'));
    }

    public function destroy(Journal $journal, JournalIssue $issue): RedirectResponse
    {
        $this->ensureIssueBelongsToJournal($journal, $issue);

        $this->issueService->delete($issue);

        return redirect()
            ->route('admin.journals.show', $journal)
            ->with('success', __('Son o‘chirildi.'));
    }

    /**
     * Security: the issue must belong to this specific journal.
     */
    private function ensureIssueBelongsToJournal(Journal $journal, JournalIssue $issue): void
    {
        abort_unless($issue->journal_id === $journal->id, 404);
    }
}
