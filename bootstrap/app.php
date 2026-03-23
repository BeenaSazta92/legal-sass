<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ensure unauthenticated API requests do not attempt to redirect to a login route.
        // ------------------------
        // Custom Middleware
        // ------------------------

        // Tenant isolation middleware
        //$middleware->push(\App\Http\Middleware\EnforceTenantIsolation::class);
        $middleware->alias([
            'tenantIsolation' => \App\Http\Middleware\EnforceTenantIsolation::class
        ]);

        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for any API request (useful when browsing via browser)
        $exceptions->shouldRenderJsonWhen(function ($request, $exception) {
            return $request->is('api/*') || $request->wantsJson();
        });

        // Handle model not found exceptions for API routes
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found'
                ], 404);
            }
        });
    })->create();
