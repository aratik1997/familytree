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
            // "admin" guards the family records — an Admin and the Super Admin
            // both pass. "super-admin" guards managing the Admins themselves.
            'admin' => \App\Http\Middleware\EnsureManagesTree::class,
            'super-admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
        ]);

        // Trusted proxies are configured in AppServiceProvider instead: this
        // closure runs before the config is loaded, so config() is not
        // available here yet.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Inside the web group, so the session it reads the chosen language
        // from has already been started.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
