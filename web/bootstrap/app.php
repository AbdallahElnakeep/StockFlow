<?php

use App\Http\Middleware\EnforceCompanyScope;
use App\Http\Middleware\SetAppLocale;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'company.scope' => EnforceCompanyScope::class,
            'active.user' => EnsureUserIsActive::class,
        ]);
        $middleware->statefulApi();
        $middleware->web(append: [EnforceCompanyScope::class, SetAppLocale::class]);
        $middleware->api(append: [EnforceCompanyScope::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'غير موجود'], 404);
            }
        });
    })->create();
