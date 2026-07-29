<?php

namespace App\Providers;

use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use App\Repositories\Contracts\BookReadingRepositoryInterface;
use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\ComputerRepositoryInterface;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;
use App\Repositories\Contracts\CopyRepositoryInterface;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\JournalCopyRepositoryInterface;
use App\Repositories\Contracts\JournalIssueRepositoryInterface;
use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Repositories\Contracts\LoanRepositoryInterface;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use App\Repositories\Contracts\NewsRepositoryInterface;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Repositories\Contracts\PeriodicalRepositoryInterface;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\SubscriptionCatalogRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Repositories\Contracts\WarningRepositoryInterface;
use App\Repositories\Eloquent\ArticleRepository;
use App\Repositories\Eloquent\AudiobookRepository;
use App\Repositories\Eloquent\AvtoreferatRepository;
use App\Repositories\Eloquent\BookReadingRepository;
use App\Repositories\Eloquent\BookRepository;
use App\Repositories\Eloquent\CatalogRepository;
use App\Repositories\Eloquent\ComputerRepository;
use App\Repositories\Eloquent\ComputerSessionRepository;
use App\Repositories\Eloquent\CopyRepository;
use App\Repositories\Eloquent\DissertationRepository;
use App\Repositories\Eloquent\EventRepository;
use App\Repositories\Eloquent\JournalCopyRepository;
use App\Repositories\Eloquent\JournalIssueRepository;
use App\Repositories\Eloquent\JournalRepository;
use App\Repositories\Eloquent\LoanRepository;
use App\Repositories\Eloquent\MenuItemRepository;
use App\Repositories\Eloquent\NewsRepository;
use App\Repositories\Eloquent\PageRepository;
use App\Repositories\Eloquent\PeriodicalRepository;
use App\Repositories\Eloquent\ReaderRepository;
use App\Repositories\Eloquent\StatisticsRepository;
use App\Repositories\Eloquent\SubscriptionCatalogRepository;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Repositories\Eloquent\VideoRepository;
use App\Repositories\Eloquent\WarningRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interface → implementation binding.
     * When a new repository is added, it is registered in this list.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ArticleRepositoryInterface::class => ArticleRepository::class,
        AudiobookRepositoryInterface::class => AudiobookRepository::class,
        AvtoreferatRepositoryInterface::class => AvtoreferatRepository::class,
        BookReadingRepositoryInterface::class => BookReadingRepository::class,
        BookRepositoryInterface::class => BookRepository::class,
        CatalogRepositoryInterface::class => CatalogRepository::class,
        ComputerRepositoryInterface::class => ComputerRepository::class,
        ComputerSessionRepositoryInterface::class => ComputerSessionRepository::class,
        CopyRepositoryInterface::class => CopyRepository::class,
        DissertationRepositoryInterface::class => DissertationRepository::class,
        EventRepositoryInterface::class => EventRepository::class,
        JournalRepositoryInterface::class => JournalRepository::class,
        JournalIssueRepositoryInterface::class => JournalIssueRepository::class,
        JournalCopyRepositoryInterface::class => JournalCopyRepository::class,
        LoanRepositoryInterface::class => LoanRepository::class,
        MenuItemRepositoryInterface::class => MenuItemRepository::class,
        NewsRepositoryInterface::class => NewsRepository::class,
        PageRepositoryInterface::class => PageRepository::class,
        PeriodicalRepositoryInterface::class => PeriodicalRepository::class,
        ReaderRepositoryInterface::class => ReaderRepository::class,
        StatisticsRepositoryInterface::class => StatisticsRepository::class,
        SubscriptionCatalogRepositoryInterface::class => SubscriptionCatalogRepository::class,
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        VideoRepositoryInterface::class => VideoRepository::class,
        WarningRepositoryInterface::class => WarningRepository::class,
    ];
}
