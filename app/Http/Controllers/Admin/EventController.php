<?php

namespace App\Http\Controllers\Admin;

use App\Data\EventData;
use App\Enums\EventRole;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventLocation;
use App\Models\News;
use App\Models\Reader;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type']);

        return view('pages.admin.events.index', [
            'events' => $this->eventService->paginate($filters),
            'filters' => $filters,
            'types' => EventType::cases(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.events.create', $this->formOptions());
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->eventService->create(EventData::fromRequest($request));

        return redirect()
            ->route('admin.events.index')
            ->with('success', __('Tadbir qo‘shildi.'));
    }

    public function edit(Event $event): View
    {
        $event->load(['locations', 'participants.reader']);

        return view('pages.admin.events.edit', [
            'event' => $event,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->eventService->update($event, EventData::fromRequest($request));

        return redirect()
            ->route('admin.events.index')
            ->with('success', __('Tadbir yangilandi.'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->eventService->delete($event);

        return redirect()
            ->route('admin.events.index')
            ->with('success', __('Tadbir o‘chirildi.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'types' => EventType::cases(),
            'roles' => EventRole::cases(),
            'locations' => EventLocation::orderBy('name')->get(),
            'newsItems' => News::orderByDesc('published_at')->get(['id', 'title']),
            'readers' => Reader::orderBy('full_name')->get(['id', 'full_name']),
        ];
    }
}
