<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerificarPrivilegio;
use App\Http\Middleware\RegistrarAccesoPagina;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            HandleInertiaRequests::class,
            RegistrarAccesoPagina::class,
        ]);

        $middleware->alias([
            'privilegio' => VerificarPrivilegio::class,
        ]);

        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'pagofacil/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
