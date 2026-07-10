<?php

namespace App\Providers;

use App\Repositories\Contracts\MenuItemRepositoryInterface;
use App\Services\LoanService;
use App\Services\Site\SectionService;
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

        // The public header and footer both link into the first content section
        // ("ARM haqida"): its first child page, or the section landing. Null when
        // no menu has been set up yet — the callers then hide the link.
        View::composer(['partials.site.header', 'partials.site.footer'], function ($view) {
            $section = app(MenuItemRepositoryInterface::class)->primarySection();
            $child = $section?->children->first();

            $view->with('armUrl', match (true) {
                $child !== null => $child->publicUrl(),
                $section !== null => route('page.show', $section->id),
                default => null,
            });
        });

        // Footer's "Bo'limlar" column: the fund's sections, so every link is real.
        View::composer('partials.site.footer', function ($view) {
            $tiles = app(SectionService::class)->tiles();

            $view->with('footerSections', $tiles->take(2)->concat($tiles->slice(-2))->values());
        });
    }
}
