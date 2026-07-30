<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\Loan;
use App\Services\AdminActivityLogService;

class LoanObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    public function created(Loan $loan): void
    {
        $this->activityLog->logChange($loan, AdminActivityAction::Created);
    }

    public function updated(Loan $loan): void
    {
        $this->activityLog->logChange($loan, AdminActivityAction::Updated);
    }

    public function deleted(Loan $loan): void
    {
        $this->activityLog->logChange($loan, AdminActivityAction::Deleted);
    }
}
