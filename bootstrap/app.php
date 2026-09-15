<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\RejectUnsafeInput::class);
        $middleware->alias([
            'staff' => \App\Http\Middleware\StaffAuthenticated::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'no.history' => \App\Http\Middleware\PreventBackHistory::class,
            'api.staff' => \App\Http\Middleware\ApiStaffAuthenticated::class,
            'api.admin' => \App\Http\Middleware\ApiAdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
