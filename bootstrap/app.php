<?php

use App\Http\Middleware\EnsureTenantIsSet;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            IdentifyTenant::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->alias([
            'tenant.identify' => IdentifyTenant::class,
            'tenant.ensure' => EnsureTenantIsSet::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
