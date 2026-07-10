<?php

namespace App\Http\Controllers\Site;

use App\Enums\PublicationKind;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodicalController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(
        private readonly PeriodicalRepositoryInterface $periodicals,
    ) {}

    /**
     * Public list of journals and newspapers, optionally filtered by kind.
     */
    public function index(Request $request): View
    {
        // An unknown ?kind is ignored rather than rejected — it is a public page.
        $kind = PublicationKind::tryFrom((string) $request->query('kind'));

        return view('pages.site.periodicals', [
            'periodicals' => $this->periodicals->paginateJournals($kind?->value, self::PER_PAGE),
            'activeKind' => $kind,
        ]);
    }
}
