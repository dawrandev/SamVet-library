<?php

namespace App\Observers;

use App\Enums\AdminActivityAction;
use App\Models\Reader;
use App\Services\AdminActivityLogService;
use App\Services\SearchIndexService;

class ReaderObserver
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
        private readonly SearchIndexService $searchIndex,
    ) {}

    /**
     * Refresh the script-independent search columns on every write, so a
     * reader stays findable whichever script the librarian types their name
     * in. These words never reach the public suggestion vocabulary — see
     * SearchIndexService::PUBLIC_MODELS.
     */
    public function saving(Reader $reader): void
    {
        $this->searchIndex->fill($reader);
    }

    /**
     * New readers get the library's shared sign-in password (hashed by the
     * model cast). Set an individual password later to override it.
     */
    public function creating(Reader $reader): void
    {
        if (empty($reader->password)) {
            $reader->password = config('arm.reader_default_password');
        }
    }

    public function created(Reader $reader): void
    {
        $this->activityLog->logChange($reader, AdminActivityAction::Created);
    }

    public function updated(Reader $reader): void
    {
        $this->activityLog->logChange($reader, AdminActivityAction::Updated);
    }

    public function deleted(Reader $reader): void
    {
        $this->activityLog->logChange($reader, AdminActivityAction::Deleted);
    }
}
