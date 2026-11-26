<?php

use App\Http\Middleware\EnsureProviderAccess;
use App\Http\Middleware\EnsureProviderPermission;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('provider') || $request->is('provider/*')) {
                return route('provider.login');
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->alias([
            'user.type' => EnsureUserType::class,
            'provider.access' => EnsureProviderAccess::class,
            'provider.permission' => EnsureProviderPermission::class,
            'super.admin' => EnsureSuperAdmin::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });
    })->create();
