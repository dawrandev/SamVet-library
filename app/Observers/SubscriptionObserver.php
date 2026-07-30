<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\Subscription;
use App\Services\AdminActivityLogService;

class SubscriptionObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    public function created(Subscription $subscription): void
    {
        $this->activityLog->logChange($subscription, AdminActivityAction::Created);
    }

    public function updated(Subscription $subscription): void
    {
        $this->activityLog->logChange($subscription, AdminActivityAction::Updated);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->activityLog->logChange($subscription, AdminActivityAction::Deleted);
    }
}
