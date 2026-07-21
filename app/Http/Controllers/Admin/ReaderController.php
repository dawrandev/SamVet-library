<?php

namespace App\Http\Controllers\Admin;

use App\Data\ReaderData;
use App\Enums\CopyCondition;
use App\Enums\Gender;
use App\Enums\LoanMaterialType;
use App\Enums\ReaderStatus;
use App\Enums\ReaderType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReaderRequest;
use App\Http\Requests\Admin\UpdateReaderRequest;
use App\Models\Computer;
use App\Models\Reader;
use App\Services\BookReadingService;
use App\Services\LoanService;
use App\Services\ReaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ReaderController extends Controller
{
    public function __construct(
        private readonly ReaderService $readerService,
        private readonly LoanService $loanService,
        private readonly BookReadingService $bookReadingService,
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

    public function show(Reader $reader, Request $request): View
    {
        $reader->load(['warnings', 'eventParticipations.event.locations', 'computerSessions.computer']);

        $materialFilters = [
            'search' => $request->input('material_search'),
            'material_type' => $request->input('material_type'),
        ];

        return view('pages.admin.readers.show', [
            'reader' => $reader,
            'loans' => $this->loanService->paginateForReader($reader->id, $materialFilters),
            'bookReadings' => $this->bookReadingService->paginateForReader($reader->id),
            'materialFilters' => $materialFilters,
            'materialTypes' => LoanMaterialType::cases(),
            'copyConditions' => CopyCondition::cases(),
            'eventParticipations' => $reader->eventParticipations->sortByDesc(fn ($p) => $p->event->date)->values(),
            // Computers picked from the registry in the "computer usage" modal —
            // only ones the librarian has assigned a hand-out number to.
            'computers' => Computer::whereNotNull('computer_number')->orderBy('computer_number')->get(),
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
        try {
            $this->readerService->delete($reader);
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.readers.index')
            ->with('success', __('Foydalanuvchi o‘chirildi.'));
    }
}
