<?php

namespace App\Http\Controllers\Admin;

use App\Data\SubscriptionData;
use App\Enums\Month;
use App\Enums\SubscriptionSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionRequest;
use App\Http\Requests\Admin\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only(['reader_id', 'journal_id', 'year', 'source']), fn ($v) => $v !== null && $v !== '');

        return view('pages.admin.subscriptions.index', [
            'subscriptions' => $this->subscriptionService->paginate($filters),
            'totalAmount' => $this->subscriptionService->sumAmount($filters),
            'filters' => $filters,
            'months' => Month::cases(),
            'sources' => SubscriptionSource::cases(),
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

    /**
     * Stream the payment receipt (proof-of-payment scan/photo) — private disk,
     * admin-only via the route's auth middleware.
     */
    public function receipt(Subscription $subscription): StreamedResponse
    {
        abort_unless($subscription->receipt_file, 404);

        return Storage::disk('local')->response($subscription->receipt_file);
    }
}
