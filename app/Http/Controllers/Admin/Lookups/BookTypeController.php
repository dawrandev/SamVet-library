<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\TranslatableLookupRequest;
use App\Models\BookType;
use App\Services\Lookups\BookTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookTypeController extends Controller
{
    public function __construct(
        private readonly BookTypeService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.book-types.index', [
            'bookTypes' => $this->service->list(),
        ]);
    }

    public function store(TranslatableLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.book-types.index')
            ->with('success', __('Kitob turi qo‘shildi.'));
    }

    public function update(TranslatableLookupRequest $request, BookType $bookType): RedirectResponse
    {
        $this->service->update($bookType, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.book-types.index')
            ->with('success', __('Kitob turi yangilandi.'));
    }

    public function destroy(BookType $bookType): RedirectResponse
    {
        $this->service->delete($bookType);

        return redirect()
            ->route('admin.lookups.book-types.index')
            ->with('success', __('Kitob turi o‘chirildi.'));
    }
}
