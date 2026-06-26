<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

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
                return $this->errorResponse(
                    [
                        'message' => $this->getMessage(class_basename($e)),
                        'error' => $e->getMessage()
                    ],
                    $this->getCode(class_basename($e))
                );
            }
        });
    })->withProviders([
        App\Providers\EventServiceProvider::class,
    ])->create();
