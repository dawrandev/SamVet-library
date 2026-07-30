<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\BookCopy;
use App\Services\AdminActivityLogService;

class BookCopyObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    public function created(BookCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Created);
    }

    public function updated(BookCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Updated);
    }

    public function deleted(BookCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Deleted);
    }
}
