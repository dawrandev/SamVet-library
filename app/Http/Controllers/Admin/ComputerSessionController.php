<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreComputerSessionRequest;
use App\Models\ComputerSession;
use App\Models\Reader;
use App\Services\ComputerSessionService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ComputerSessionController extends Controller
{
    public function __construct(
        private readonly ComputerSessionService $computerSessionService,
    ) {}

    public function store(StoreComputerSessionRequest $request, Reader $reader): RedirectResponse
    {
        $this->computerSessionService->create($reader, $request->validated());

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Kompyuterdan foydalanish yozuvi qo‘shildi.'));
    }

    public function destroy(Reader $reader, ComputerSession $computerSession): RedirectResponse
    {
        // Xavfsizlik: yozuv shu a'zoga tegishli ekanini tekshiramiz.
        if ($computerSession->reader_id !== $reader->id) {
            throw new NotFoundHttpException();
        }

        $this->computerSessionService->delete($computerSession);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Yozuv o‘chirildi.'));
    }
}
