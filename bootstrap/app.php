<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Required when Laravel is accessed through ngrok or another reverse proxy
        // so generated URLs, redirects, cookies, and signed URLs retain HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'superadmin' => EnsureUserIsSuperAdmin::class,
            'permission' => EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response): Response {
            $status = $response->getStatusCode();
            $supportedStatuses = [403, 404, 419, 429, 500, 503];

            if (
                request()->expectsJson()
                || ! in_array($status, $supportedStatuses, true)
                || ($status === 500 && config('app.debug'))
            ) {
                return $response;
            }

            return Inertia::render('Errors/Status', [
                'status' => $status,
                'isAuthenticated' => auth()->check(),
                'canAccessDashboard' => auth()->user()?->isAdmin()
                    || auth()->user()?->hasPermission('dashboard.view'),
            ])->toResponse(request())->setStatusCode($status);
        });
    })->create();
