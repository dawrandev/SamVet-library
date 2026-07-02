<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Models\Reader;
use App\Models\ReaderEvent;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    public function store(StoreEventRequest $request, Reader $reader): RedirectResponse
    {
        $this->eventService->create($reader, $request->validated());

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Tadbir qo‘shildi.'));
    }

    public function destroy(Reader $reader, ReaderEvent $event): RedirectResponse
    {
        // Xavfsizlik: tadbir shu a'zoga tegishli ekanini tekshiramiz.
        if ($event->reader_id !== $reader->id) {
            throw new NotFoundHttpException();
        }

        $this->eventService->delete($event);

        return redirect()
            ->route('admin.readers.show', $reader)
            ->with('success', __('Tadbir o‘chirildi.'));
    }
}
