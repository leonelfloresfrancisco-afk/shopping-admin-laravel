<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | Proxies de confianza
        |--------------------------------------------------------------------------
        |
        | Render recibe las solicitudes HTTPS y las reenvía internamente al
        | contenedor. Laravel necesita confiar en ese proxy para reconocer
        | correctamente el protocolo, host y URLs firmadas.
        |
        | Esto es especialmente importante para las URLs temporales firmadas
        | utilizadas durante la subida de archivos de Livewire.
        |
        */

        $middleware->trustProxies(
            at: '*'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |--------------------------------------------------------------------------
        | Respuestas JSON
        |--------------------------------------------------------------------------
        |
        | Las rutas API y las solicitudes que esperan JSON recibirán los errores
        | en formato JSON. Las páginas normales conservarán la respuesta HTML.
        |
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool =>
                $request->is('api/*')
                || $request->expectsJson(),
        );
    })
    ->create();