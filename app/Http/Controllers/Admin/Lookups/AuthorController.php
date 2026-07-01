<?php

namespace App\Http\Controllers\Admin\Lookups;

use App\Data\LookupData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lookups\SimpleLookupRequest;
use App\Models\Author;
use App\Services\Lookups\AuthorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function __construct(
        private readonly AuthorService $service,
    ) {}

    public function index(): View
    {
        return view('pages.admin.lookups.authors.index', [
            'authors' => $this->service->list(),
        ]);
    }

    public function store(SimpleLookupRequest $request): RedirectResponse
    {
        $this->service->create(LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.authors.index')
            ->with('success', __('Muallif qo‘shildi.'));
    }

    public function update(SimpleLookupRequest $request, Author $author): RedirectResponse
    {
        $this->service->update($author, LookupData::fromRequest($request));

        return redirect()
            ->route('admin.lookups.authors.index')
            ->with('success', __('Muallif yangilandi.'));
    }

    public function destroy(Author $author): RedirectResponse
    {
        $this->service->delete($author);

        return redirect()
            ->route('admin.lookups.authors.index')
            ->with('success', __('Muallif o‘chirildi.'));
    }
}
