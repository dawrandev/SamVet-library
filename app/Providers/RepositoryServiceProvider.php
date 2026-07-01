<?php

namespace App\Providers;

use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Eloquent\BookRepository;
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
    ];
}
