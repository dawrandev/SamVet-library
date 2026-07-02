<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockReaderRequest;
use App\Models\Reader;
use App\Services\ReaderStatusService;
use Illuminate\Http\RedirectResponse;

class ReaderStatusController extends Controller
{
    public function __construct(
        private readonly ReaderStatusService $statusService,
    ) {}

    public function block(BlockReaderRequest $request, Reader $reader): RedirectResponse
    {
        $this->statusService->block(
            $reader,
            $request->input('blocked_until') ?: null,
            $request->input('block_reason'),
        );

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Foydalanuvchi bloklandi.'));
    }

    public function finish(Reader $reader): RedirectResponse
    {
        $this->statusService->finish($reader);

        return redirect()
            ->route('admin.readers.index')
            ->with('success', __('Kutubxonadan foydalanish tugatildi.'));
    }

    public function restore(Reader $reader): RedirectResponse
    {
        $this->statusService->restore($reader);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Foydalanuvchi tiklandi.'));
    }
}
