<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register named middleware aliases
        $middleware->alias([
            'role'       => RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);

        // CORS headers for the Angular frontend
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Prevent caching of API responses
        $middleware->api(append: [
            \Illuminate\Http\Middleware\SetCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for all API exceptions
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                // JWT-specific errors
                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                    return response()->json(['success' => false, 'message' => 'Token has expired.'], 401);
                }
                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                    return response()->json(['success' => false, 'message' => 'Token is invalid.'], 401);
                }
                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
                    return response()->json(['success' => false, 'message' => 'Token is absent.'], 401);
                }

                // Validation errors
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                // Model not found
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(['success' => false, 'message' => 'Resource not found.'], 404);
                }

                // Authorization
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
                }

                // Generic
                return response()->json([
                    'success' => false,
                    'message' => app()->environment('production')
                        ? 'An unexpected error occurred.'
                        : $e->getMessage(),
                ], $status >= 100 && $status < 600 ? $status : 500);
            }
        });
    })
    ->create();
