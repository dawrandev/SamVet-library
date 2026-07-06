<?php

namespace App\Providers;

use App\Services\LoanService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pass the number of overdue books to the admin layout and its
        // partials (header/sidebar) for the notification badge. Cached for 60s
        // (to let time pass), but LoanService clears it immediately when a loan changes.
        View::composer('layouts.admin', function ($view) {
            $count = cache()->remember(
                LoanService::OVERDUE_CACHE_KEY,
                60,
                fn () => app(LoanService::class)->overdueCount(),
            );

            $view->with('overdueLoansCount', $count);
        });
    }
}
