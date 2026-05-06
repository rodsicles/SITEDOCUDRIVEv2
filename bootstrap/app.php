<?php

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
        $middleware->alias([
            'role'                  => \App\Http\Middleware\RoleMiddleware::class,
            'no.back'               => \App\Http\Middleware\PreventBackHistory::class,
            'password.changed'      => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        // Apply the password-change gate to every web request. Inside the
        // middleware, unauthenticated requests pass through untouched.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsurePasswordChanged::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
