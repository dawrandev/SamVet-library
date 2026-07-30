<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function __construct(
        private readonly AdminActivityLogService $activityLog,
    ) {}

    /**
     * Who changed what, and when — read-only audit trail for admin actions
     * on sensitive records (readers' PII, inventory, subscriptions, loans).
     */
    public function index(Request $request): View
    {
        $filters = [
            'subject_type' => $request->string('subject_type')->trim()->toString() ?: null,
            'action' => $request->string('action')->trim()->toString() ?: null,
        ];

        return view('pages.admin.activity-log.index', [
            'logs' => $this->activityLog->paginate($filters),
            'filters' => $filters,
        ]);
    }
}
