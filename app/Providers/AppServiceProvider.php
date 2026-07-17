<?php

namespace App\Providers;

use App\Models\BookCopy;
use App\Models\JournalCopy;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use App\Services\ComputerSessionService;
use App\Services\LoanService;
use App\Services\Site\SectionService;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Short, stable values for Loan::loanable_type — matches this project's
        // convention of storing snake_case values (not FQCNs) for typed columns.
        Relation::enforceMorphMap([
            'book_copy' => BookCopy::class,
            'journal_copy' => JournalCopy::class,
        ]);

        // Pass the number of overdue books and expired-unfinished computer
        // sessions to the admin layout and its partials (header/sidebar) for
        // the notification badges. Cached for 60s (to let time pass), but each
        // service clears its own cache immediately when its state changes.
        View::composer('layouts.admin', function ($view) {
            $overdueCount = cache()->remember(
                LoanService::OVERDUE_CACHE_KEY,
                60,
                fn () => app(LoanService::class)->overdueCount(),
            );

            $expiredComputerSessionsCount = cache()->remember(
                ComputerSessionService::EXPIRED_CACHE_KEY,
                60,
                fn () => app(ComputerSessionService::class)->expiredCount(),
            );

            $view->with([
                'overdueLoansCount' => $overdueCount,
                'expiredComputerSessionsCount' => $expiredComputerSessionsCount,
            ]);
        });

        // The public navbar renders the admin-built menu tree: active top-level
        // items, each with its active children as a dropdown. Empty when no menu
        // has been set up yet — the header then shows only its fixed anchors.
        View::composer('partials.site.header', function ($view) {
            $view->with('navMenu', app(MenuItemRepositoryInterface::class)->publicTree());
        });

        // The footer still links into the first content section ("ARM haqida"):
        // its first child page, or the section landing. Null when no menu exists.
        View::composer('partials.site.footer', function ($view) {
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
