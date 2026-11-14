<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // One deterministic 404 presentation for both unmatched browser routes
        // and intentionally unavailable feature routes (for example, a disabled
        // SSO entrypoint).  Only NotFoundHttpException is affected, so 403, 405,
        // 419 and 500 semantics are preserved.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            // API/JSON clients get a plain JSON body.  Inertia XHR requests are
            // excluded because they need the Inertia page payload instead.
            if (($request->is('api/*') || $request->expectsJson()) && ! $request->header('X-Inertia')) {
                return response()->json(['message' => 'Not Found'], 404);
            }

            try {
                // Do not serialize an arbitrary or attacker-controlled route
                // path into the HTML/Inertia page payload.
                Inertia::resolveUrlUsing(static fn (Request $request): string => '/404');

                $response = Inertia::render('NotFound')->toResponse($request)->setStatusCode(404);

                if (! $request->header('X-Inertia')) {
                    $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                }

                return $response;
            } catch (\Throwable) {
                // Safe fallback when frontend assets are unavailable, avoiding
                // recursive error rendering that needs the Vite manifest.
                return response(
                    '<!doctype html><html lang="en"><head><meta charset="utf-8">'
                    .'<meta name="viewport" content="width=device-width, initial-scale=1">'
                    .'<title>404 - Page not found</title></head><body>'
                    .'<main><h1>Page not found</h1><p>That page could not be found.</p>'
                    .'<p><a href="/">Go home</a></p></main></body></html>',
                    404,
                )->header('Content-Type', 'text/html; charset=UTF-8');
            } finally {
                Inertia::resolveUrlUsing(null);
            }
        });
    })->create();
