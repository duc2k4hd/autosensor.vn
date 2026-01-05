<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
        ]);

        // Không dùng redirectGuestsTo để tránh ảnh hưởng SEO
        // Admin routes dùng middleware 'admin' (CheckAdmin) tự xử lý authentication và redirect

        // Rate limiting for API routes
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Rate limiting for web routes (120 requests per minute)
        // Tăng lên 120 để cho phép crawl/indexing và tránh lỗi 429 khi test
        // $middleware->web(append: [
        //     \Illuminate\Routing\Middleware\ThrottleRequests::class.':1000,1',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions
        $exceptions->report(function (Throwable $e) {
            Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });

        // Render custom error pages
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('admin/*')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return redirect()->route('admin.login');
                }
            }
        });
    })->create();
