<?php

namespace App\Http\Controllers\Admin;

use App\Data\SubscriberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriberRequest;
use App\Http\Requests\Admin\UpdateSubscriberRequest;
use App\Models\Subscriber;
use App\Services\SubscriberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function __construct(
        private readonly SubscriberService $subscriberService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search']);

        return view('pages.admin.subscribers.index', [
            'subscribers' => $this->subscriberService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.subscribers.create');
    }

    public function store(StoreSubscriberRequest $request): RedirectResponse
    {
        $this->subscriberService->create(SubscriberData::fromRequest($request));

        return redirect()
            ->route('admin.subscribers.index')
            ->with('success', __('Obunachi qo‘shildi.'));
    }

    public function edit(Subscriber $subscriber): View
    {
        return view('pages.admin.subscribers.edit', ['subscriber' => $subscriber]);
    }

    public function update(UpdateSubscriberRequest $request, Subscriber $subscriber): RedirectResponse
    {
        $this->subscriberService->update($subscriber, SubscriberData::fromRequest($request));

        return redirect()
            ->route('admin.subscribers.index')
            ->with('success', __('Obunachi yangilandi.'));
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $this->subscriberService->delete($subscriber);

        return redirect()
            ->route('admin.subscribers.index')
            ->with('success', __('Obunachi o‘chirildi.'));
    }
}
