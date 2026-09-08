<?php

use App\Http\Middleware\EnsureReaderIsAuthenticated;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Services\ServerLimitsService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

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
            SecurityHeaders::class,
        ]);

        // Reading endpoints require a signed-in reader (not an admin user).
        $middleware->alias([
            'reader.auth' => EnsureReaderIsAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A file bigger than post_max_size never reaches validation: PHP throws
        // the request body away before Laravel runs, so the `max:` rule on the
        // form cannot fire and the visitor gets a bare 413 with no hint that
        // the file was simply too large. This turns it back into the message
        // the form would have shown, on the form.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $limit = ServerLimitsService::format(ServerLimitsService::effectiveUploadBytes());

            $message = __('Fayl juda katta. Server qabul qiladigan eng katta hajm: :limit', ['limit' => $limit]);

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return back()->withErrors(['file' => $message]);
        });
    })->create();
