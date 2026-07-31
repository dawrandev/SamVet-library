<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\AvtoreferatCopy;
use App\Services\AdminActivityLogService;

class AvtoreferatCopyObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    public function created(AvtoreferatCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Created);
    }

    public function updated(AvtoreferatCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Updated);
    }

    public function deleted(AvtoreferatCopy $copy): void
    {
        $this->activityLog->logChange($copy, AdminActivityAction::Deleted);
    }
}
