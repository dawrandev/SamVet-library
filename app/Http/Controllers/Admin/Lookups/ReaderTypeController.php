<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\ReaderTypeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\ReaderTypeRequest;
use App\Models\ReaderType;
use App\Services\Lookups\ReaderTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReaderTypeController extends Controller
{
    public function __construct(
        private readonly ReaderTypeService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.reader-types.index', [
            'readerTypes' => $this->service->list(),
        ]);
    }

    public function store(ReaderTypeRequest $request): RedirectResponse
    {
        $this->service->create(ReaderTypeData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.reader-types.index')
            ->with('success', __('Foydalanuvchi turi qo‘shildi.'));
    }

    public function update(ReaderTypeRequest $request, ReaderType $readerType): RedirectResponse
    {
        $this->service->update($readerType, ReaderTypeData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.reader-types.index')
            ->with('success', __('Foydalanuvchi turi yangilandi.'));
    }

    public function destroy(ReaderType $readerType): RedirectResponse
    {
        $this->service->delete($readerType);

        return redirect()
            ->route('admin.lookups.reader-types.index')
            ->with('success', __('Foydalanuvchi turi o‘chirildi.'));
    }
}
