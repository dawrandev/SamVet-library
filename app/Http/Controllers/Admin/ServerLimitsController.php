<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServerLimitsService;
use Illuminate\View\View;

class ServerLimitsController extends Controller
{
    public function __construct(
        private readonly ServerLimitsService $limits,
    ) {}

    /**
     * Read-only view of the PHP limits this app actually runs under.
     *
     * Deliberately not phpinfo(): that dumps environment variables, absolute
     * paths and loaded module details — far more than is needed here, on a
     * page whose whole job is answering "how big a file can this server
     * really take". Only the upload/memory/OPcache settings are shown.
     */
    public function index(): View
    {
        return view('pages.admin.server-limits.index', [
            'uploadLimits' => $this->limits->uploadLimits(),
            'opcache' => $this->limits->opcache(),
            'verdict' => $this->limits->verdict(),
        ]);
    }
}
