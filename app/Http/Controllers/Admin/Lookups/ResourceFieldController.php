<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\ResourceField;
use App\Services\Lookups\ResourceFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ResourceFieldController extends Controller
{
    public function __construct(
        private readonly ResourceFieldService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.resource-fields.index', [
            'resourceFields' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.resource-fields.index')
            ->with('success', __('Resurs sohasi qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, ResourceField $resourceField): RedirectResponse
    {
        $this->service->update($resourceField, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.resource-fields.index')
            ->with('success', __('Resurs sohasi yangilandi.'));
    }

    public function destroy(ResourceField $resourceField): RedirectResponse
    {
        $this->service->delete($resourceField);

        return redirect()
            ->route('admin.lookups.resource-fields.index')
            ->with('success', __('Resurs sohasi o‘chirildi.'));
    }
}
