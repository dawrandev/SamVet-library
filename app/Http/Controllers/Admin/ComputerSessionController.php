<?php

namespace App\Http\Controllers\Admin;

use App\Data\ComputerSessionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtendComputerSessionRequest;
use App\Http\Requests\Admin\StoreComputerSessionRequest;
use App\Models\ComputerSession;
use App\Models\Reader;
use App\Services\ComputerSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComputerSessionController extends Controller
{
    public function __construct(
        private readonly ComputerSessionService $computerSessionService,
    ) {}

    /**
     * Cross-reader list of computer usage records (active / expired / finished).
     */
    public function index(Request $request): View
    {
        $scope = $request->input('scope', 'active');

        if (! in_array($scope, ['active', 'expired', 'finished'], true)) {
            $scope = 'active';
        }

        $filters = ['scope' => $scope, 'search' => $request->input('search')];

        return view('pages.admin.computer-sessions.index', [
            'sessions' => $this->computerSessionService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreComputerSessionRequest $request, Reader $reader): RedirectResponse
    {
        $this->computerSessionService->create($reader, ComputerSessionData::fromRequest($request));

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Kompyuterdan foydalanish yozuvi qo‘shildi.'));
    }

    public function destroy(Reader $reader, ComputerSession $computerSession): RedirectResponse
    {
        // Security: verify the record belongs to this member.
        if ($computerSession->reader_id !== $reader->id) {
            throw new NotFoundHttpException();
        }

        $this->computerSessionService->delete($computerSession);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Yozuv o‘chirildi.'));
    }

    /**
     * "Tugatish" — mark a session finished right now. Reachable from both the
     * reader's own show page and the cross-reader index, so it redirects back
     * to wherever it was triggered from rather than a fixed route.
     */
    public function finish(ComputerSession $computerSession): RedirectResponse
    {
        $this->computerSessionService->finish($computerSession);

        return back()->with('success', __('Seans tugatildi.'));
    }

    public function extend(ExtendComputerSessionRequest $request, ComputerSession $computerSession): RedirectResponse
    {
        $this->computerSessionService->extend($computerSession, $request->integer('minutes'));

        return back()->with('success', __('Vaqt uzaytirildi.'));
    }
}
