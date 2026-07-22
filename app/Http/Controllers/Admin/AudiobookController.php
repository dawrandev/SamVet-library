<?php

namespace App\Http\Controllers\Admin;

use App\Data\AudiobookData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAudiobookRequest;
use App\Http\Requests\Admin\UpdateAudiobookRequest;
use App\Models\Audiobook;
use App\Services\AudiobookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudiobookController extends Controller
{
    public function __construct(
        private readonly AudiobookService $audiobookService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.admin.audiobooks.index', [
            'audiobooks' => $this->audiobookService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Audiobook $audiobook): View
    {
        $audiobook->load('tracks');

        return view('pages.admin.audiobooks.show', ['audiobook' => $audiobook]);
    }

    public function create(): View
    {
        return view('pages.admin.audiobooks.create');
    }

    public function store(StoreAudiobookRequest $request): RedirectResponse
    {
        $audiobook = $this->audiobookService->create(AudiobookData::fromRequest($request));

        return redirect()
            ->route('admin.audiobooks.show', $audiobook)
            ->with('success', __('Audiokitob yaratildi. Endi audiolarni qo‘shishingiz mumkin.'));
    }

    public function edit(Audiobook $audiobook): View
    {
        return view('pages.admin.audiobooks.edit', ['audiobook' => $audiobook]);
    }

    public function update(UpdateAudiobookRequest $request, Audiobook $audiobook): RedirectResponse
    {
        $this->audiobookService->update($audiobook, AudiobookData::fromRequest($request));

        return redirect()
            ->route('admin.audiobooks.index')
            ->with('success', __('Audiokitob yangilandi.'));
    }

    public function destroy(Audiobook $audiobook): RedirectResponse
    {
        $this->audiobookService->delete($audiobook);

        return redirect()
            ->route('admin.audiobooks.index')
            ->with('success', __('Audiokitob o‘chirildi.'));
    }
}
