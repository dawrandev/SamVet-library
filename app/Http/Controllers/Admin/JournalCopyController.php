<?php

namespace App\Http\Controllers\Admin;

use App\Data\JournalCopyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalCopyRequest;
use App\Http\Requests\Admin\UpdateJournalCopyRequest;
use App\Models\JournalCopy;
use App\Models\JournalIssue;
use App\Services\JournalCopyService;
use Illuminate\Http\RedirectResponse;

class JournalCopyController extends Controller
{
    public function __construct(
        private readonly JournalCopyService $copyService,
    ) {}

    public function store(StoreJournalCopyRequest $request, JournalIssue $journalIssue): RedirectResponse
    {
        $this->copyService->create($journalIssue, JournalCopyData::fromRequest($request));

        return $this->backToIssue($journalIssue, __('Nusxa qo‘shildi.'));
    }

    public function update(UpdateJournalCopyRequest $request, JournalIssue $journalIssue, JournalCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToIssue($journalIssue, $copy);

        $this->copyService->update($copy, JournalCopyData::fromRequest($request));

        return $this->backToIssue($journalIssue, __('Nusxa yangilandi.'));
    }

    public function destroy(JournalIssue $journalIssue, JournalCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToIssue($journalIssue, $copy);

        $this->copyService->delete($copy);

        return $this->backToIssue($journalIssue, __('Nusxa o‘chirildi.'));
    }

    private function backToIssue(JournalIssue $journalIssue, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.journals.issues.show', [$journalIssue->journal_id, $journalIssue])
            ->with('success', $message);
    }

    /**
     * Xavfsizlik: nusxa aynan shu songa tegishli bo'lishi shart.
     */
    private function ensureCopyBelongsToIssue(JournalIssue $journalIssue, JournalCopy $copy): void
    {
        abort_unless($copy->journal_issue_id === $journalIssue->id, 404);
    }
}
