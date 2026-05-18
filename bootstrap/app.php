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
        $middleware->alias([
            'auth'            => \App\Http\Middleware\Authenticate::class,
            'admin'           => \App\Http\Middleware\AdminMiddleware::class,
            'stream.token'    => \App\Http\Middleware\StreamTokenMiddleware::class,
            'security.headers'=> \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'anti.hotlink'    => \App\Http\Middleware\AntiHotlinkMiddleware::class,
        ]);

        // Appliquer les headers de sécurité sur toutes les réponses API
        $middleware->appendToGroup('api', \App\Http\Middleware\SecurityHeadersMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
