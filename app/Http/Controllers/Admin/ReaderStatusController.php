<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockReaderRequest;
use App\Http\Requests\Admin\FinishReaderRequest;
use App\Models\Reader;
use App\Services\ReaderStatusService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ReaderStatusController extends Controller
{
    public function __construct(
        private readonly ReaderStatusService $statusService,
    ) {}

    public function block(BlockReaderRequest $request, Reader $reader): RedirectResponse
    {
        try {
            $this->statusService->block(
                $reader,
                $request->input('blocked_until') ?: null,
                $request->string('block_reason')->toString(),
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.readers.show', $reader)
                ->withErrors(['block_reason' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Foydalanuvchi bloklandi.'));
    }

    public function finish(FinishReaderRequest $request, Reader $reader): RedirectResponse
    {
        try {
            $this->statusService->finish($reader, $request->string('left_reason')->toString());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.readers.show', $reader)
                ->withErrors(['left_reason' => $e->getMessage()]);
        }

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
