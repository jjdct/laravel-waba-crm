<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        # health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // El método respond es el "filtro final" antes de enviar la respuesta al navegador
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            
            // Condiciones para mostrar tu diseño Error.vue:
            // 1. NO estamos en modo debug (APP_DEBUG=false en el .env)
            // 2. El código de estado es uno de los que manejas en tu componente
            if (! config('app.debug') && in_array($response->getStatusCode(), [403, 404, 419, 500, 503])) {
                return Inertia::render('Error', [
                    'status' => $response->getStatusCode()
                ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });

    })->create();