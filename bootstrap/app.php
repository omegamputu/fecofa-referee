<?php

use App\Http\Middleware\MustSetPassword;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'must_set_password' => MustSetPassword::class,
            'set_locale'         => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404
        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->view('errors.404', [], 404);
        });

        // 403
        $exceptions->render(function (AuthorizationException $e) {
            return response()->view('errors.403', [], 403);
        });

        // 500 (fallback)
        $exceptions->render(function (Throwable $e) {
            return response()->view('errors.500', ['exception' => $e], 500);
        });
    })->create();
