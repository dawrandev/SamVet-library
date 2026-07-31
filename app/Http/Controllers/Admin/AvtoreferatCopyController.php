<?php

namespace App\Http\Controllers\Admin;

use App\Data\AvtoreferatCopyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAvtoreferatCopyRequest;
use App\Http\Requests\Admin\UpdateAvtoreferatCopyRequest;
use App\Models\Avtoreferat;
use App\Models\AvtoreferatCopy;
use App\Services\AvtoreferatCopyService;
use Illuminate\Http\RedirectResponse;

class AvtoreferatCopyController extends Controller
{
    public function __construct(
        private readonly AvtoreferatCopyService $copyService,
    ) {}

    public function store(StoreAvtoreferatCopyRequest $request, Avtoreferat $avtoreferat): RedirectResponse
    {
        $this->copyService->create($avtoreferat, AvtoreferatCopyData::fromRequest($request));

        return redirect()
            ->route('admin.avtoreferats.show', $avtoreferat)
            ->with('success', __('Nusxa qo‘shildi.'));
    }

    public function update(UpdateAvtoreferatCopyRequest $request, Avtoreferat $avtoreferat, AvtoreferatCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToAvtoreferat($avtoreferat, $copy);

        $this->copyService->update($copy, AvtoreferatCopyData::fromRequest($request));

        return redirect()
            ->route('admin.avtoreferats.show', $avtoreferat)
            ->with('success', __('Nusxa yangilandi.'));
    }

    public function destroy(Avtoreferat $avtoreferat, AvtoreferatCopy $copy): RedirectResponse
    {
        $this->ensureCopyBelongsToAvtoreferat($avtoreferat, $copy);

        $this->copyService->delete($copy);

        return redirect()
            ->route('admin.avtoreferats.show', $avtoreferat)
            ->with('success', __('Nusxa o‘chirildi.'));
    }

    /**
     * Security: the copy must belong to this specific avtoreferat.
     */
    private function ensureCopyBelongsToAvtoreferat(Avtoreferat $avtoreferat, AvtoreferatCopy $copy): void
    {
        abort_unless($copy->avtoreferat_id === $avtoreferat->id, 404);
    }
}
