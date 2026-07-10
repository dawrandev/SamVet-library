<?php

use App\Http\Middleware\EnsureReaderIsAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Apply the selected locale on every web request.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Reading endpoints require a signed-in reader (not an admin user).
        $middleware->alias([
            'reader.auth' => EnsureReaderIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
