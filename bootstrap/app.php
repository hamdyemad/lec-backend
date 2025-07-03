<?php

use App\Http\Middleware\Translate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/admin.php',
            __DIR__.'/../routes/lawyer.php',
            __DIR__.'/../routes/client.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
         $middleware->group('translate', [
            Translate::class,
        ]);

        $middleware->alias([
            'api_key' => \App\Http\Middleware\ApiKey::class,
            'auth' => \App\Http\Middleware\CustomAuthenticate::class,
            'auth_type' => \App\Http\Middleware\AuthenticationType::class,
            'client_check' => \App\Http\Middleware\ClientCheck::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })
    ->create();
