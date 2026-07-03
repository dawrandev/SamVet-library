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
        // Admin layout va uning partiallariga (header/sidebar) muddati o'tgan
        // kitoblar sonini uzatamiz — bildirishnoma badge uchun. Kesh 60s (vaqt
        // o'tishi uchun), lekin oldi-berdi o'zgarganda LoanService darhol tozalaydi.
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
