<?php

namespace App\Http\Controllers\Admin;

use App\Data\SubscriptionData;
use App\Enums\Month;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only(['reader_id', 'journal_id', 'year']), fn ($v) => $v !== null && $v !== '');

        return view('pages.admin.subscriptions.index', [
            'subscriptions' => $this->subscriptionService->paginate($filters),
            'totalAmount' => $this->subscriptionService->sumAmount($filters),
            'filters' => $filters,
            'months' => Month::cases(),
            ...$this->subscriptionService->formOptions(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $this->subscriptionService->create(SubscriptionData::fromRequest($request));

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', __('Obuna qo‘shildi.'));
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->subscriptionService->update($subscription, SubscriptionData::fromRequest($request));

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', __('Obuna yangilandi.'));
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $this->subscriptionService->delete($subscription);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', __('Obuna o‘chirildi.'));
    }
}
