<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'can' => \App\Http\Middleware\SpatieCheckRoleAndPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                // Sin sesión o con el token vencido: 401 (aquí no existe $this, no se usan los traits)
                return response()->json([
                    'error' => [
                        'message' => 'No autenticado. ',
                        'error' => $e->getMessage()
                    ]
                ], 401);
            }
        });
    })->withProviders([
        App\Providers\EventServiceProvider::class,
    ])->create();
