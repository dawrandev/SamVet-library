<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\PostBranch;
use App\Services\Lookups\PostBranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PostBranchController extends Controller
{
    public function __construct(
        private readonly PostBranchService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.post-branches.index', [
            'branches' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.post-branches.index')
            ->with('success', __('Pochta filiali qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, PostBranch $postBranch): RedirectResponse
    {
        $this->service->update($postBranch, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.post-branches.index')
            ->with('success', __('Pochta filiali yangilandi.'));
    }

    public function destroy(PostBranch $postBranch): RedirectResponse
    {
        $this->service->delete($postBranch);

        return redirect()
            ->route('admin.lookups.post-branches.index')
            ->with('success', __('Pochta filiali o‘chirildi.'));
    }
}
