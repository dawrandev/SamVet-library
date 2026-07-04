<?php

namespace App\Providers;

use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Contracts\ComputerSessionRepositoryInterface;
use App\Repositories\Contracts\CopyRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\JournalCopyRepositoryInterface;
use App\Repositories\Contracts\JournalIssueRepositoryInterface;
use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Repositories\Contracts\LoanRepositoryInterface;
use App\Repositories\Contracts\MenuItemRepositoryInterface;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use App\Repositories\Contracts\WarningRepositoryInterface;
use App\Repositories\Eloquent\BookRepository;
use App\Repositories\Eloquent\ComputerSessionRepository;
use App\Repositories\Eloquent\CopyRepository;
use App\Repositories\Eloquent\EventRepository;
use App\Repositories\Eloquent\JournalCopyRepository;
use App\Repositories\Eloquent\JournalIssueRepository;
use App\Repositories\Eloquent\JournalRepository;
use App\Repositories\Eloquent\LoanRepository;
use App\Repositories\Eloquent\MenuItemRepository;
use App\Repositories\Eloquent\ReaderRepository;
use App\Repositories\Eloquent\WarningRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interfeys → implementatsiya bog'lash.
     * Yangi repozitoriy qo'shilganda shu ro'yxatga qo'shiladi.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        BookRepositoryInterface::class => BookRepository::class,
        ComputerSessionRepositoryInterface::class => ComputerSessionRepository::class,
        CopyRepositoryInterface::class => CopyRepository::class,
        EventRepositoryInterface::class => EventRepository::class,
        JournalRepositoryInterface::class => JournalRepository::class,
        JournalIssueRepositoryInterface::class => JournalIssueRepository::class,
        JournalCopyRepositoryInterface::class => JournalCopyRepository::class,
        LoanRepositoryInterface::class => LoanRepository::class,
        MenuItemRepositoryInterface::class => MenuItemRepository::class,
        ReaderRepositoryInterface::class => ReaderRepository::class,
        WarningRepositoryInterface::class => WarningRepository::class,
    ];
}
