<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWarningRequest;
use App\Models\Reader;
use App\Models\ReaderWarning;
use App\Services\WarningService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WarningController extends Controller
{
    public function __construct(
        private readonly WarningService $warningService,
    ) {}

    public function store(StoreWarningRequest $request, Reader $reader): RedirectResponse
    {
        $this->warningService->add(
            $reader,
            $request->string('reason')->toString(),
            $request->input('note'),
        );

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Ogohlantirish berildi.'));
    }

    public function destroy(Reader $reader, ReaderWarning $warning): RedirectResponse
    {
        // Xavfsizlik: ogohlantirish shu a'zoga tegishli ekanini tekshiramiz.
        if ($warning->reader_id !== $reader->id) {
            throw new NotFoundHttpException();
        }

        $this->warningService->delete($warning);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Ogohlantirish o‘chirildi.'));
    }
}
