<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'admin_sys/*',
            'api/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\TenantMiddleware::class,
        ]);
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'super_admin.auth' => \App\Http\Middleware\SuperAdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $logData = sprintf(
                "[%s] CSRF MISMATCH on %s %s\nSession Token: %s\nRequest Token: %s\nSession ID: %s\nCookie: %s\n\n",
                date('Y-m-d H:i:s'),
                $request->method(),
                $request->fullUrl(),
                $request->session()->token(),
                $request->input('_token') ?? $request->header('X-CSRF-TOKEN'),
                $request->session()->getId(),
                json_encode($request->cookies->all())
            );
            file_put_contents(storage_path('logs/csrf_mismatch.log'), $logData, FILE_APPEND);
        });
    })->create();
