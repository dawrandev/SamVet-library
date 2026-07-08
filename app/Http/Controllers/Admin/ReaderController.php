<?php

namespace App\Http\Controllers\Admin;

use App\Data\ReaderData;
use App\Enums\Gender;
use App\Enums\ReaderStatus;
use App\Enums\ReaderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReaderRequest;
use App\Http\Requests\Admin\UpdateReaderRequest;
use App\Models\Computer;
use App\Models\Reader;
use App\Services\ReaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function __construct(
        private readonly ReaderService $readerService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'status']);

        return view('pages.admin.readers.index', [
            'readers' => $this->readerService->paginate($filters),
            'filters' => $filters,
            'types' => ReaderType::cases(),
            'statuses' => ReaderStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.readers.create', [
            'types' => ReaderType::cases(),
            'statuses' => ReaderStatus::cases(),
            'genders' => Gender::cases(),
        ]);
    }

    public function store(StoreReaderRequest $request): RedirectResponse
    {
        $reader = $this->readerService->create(ReaderData::fromRequest($request));

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Foydalanuvchi yaratildi.'));
    }

    public function show(Reader $reader): View
    {
        $reader->load(['loans.copy.book.authors', 'warnings', 'events', 'computerSessions.computer']);

        return view('pages.admin.readers.show', [
            'reader' => $reader,
            // Computers picked from the registry in the "computer usage" modal
            'computers' => Computer::orderBy('model')->get(),
        ]);
    }

    public function edit(Reader $reader): View
    {
        return view('pages.admin.readers.edit', [
            'reader' => $reader,
            'types' => ReaderType::cases(),
            'statuses' => ReaderStatus::cases(),
            'genders' => Gender::cases(),
        ]);
    }

    public function update(UpdateReaderRequest $request, Reader $reader): RedirectResponse
    {
        $this->readerService->update($reader, ReaderData::fromRequest($request));

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Foydalanuvchi yangilandi.'));
    }

    public function destroy(Reader $reader): RedirectResponse
    {
        $this->readerService->delete($reader);

        return redirect()
            ->route('admin.readers.index')
            ->with('success', __('Foydalanuvchi o‘chirildi.'));
    }
}
