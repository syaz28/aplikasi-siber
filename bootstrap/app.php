<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load admin routes
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Exclude routes from CSRF verification
        // These routes are protected by 'auth' middleware instead
        $middleware->validateCsrfTokens(except: [
            // Auth routes
            'logout',
            'login',
            'register',
            'confirm-password',
            'reset-password',
            'forgot-password',
            'email/verification-notification',
            'password',
            
            // Admin routes
            'admin/*',
            
            // User management
            'users/*',
            
            // Laporan routes
            'laporan/*',
            
            // Pawas routes
            'pawas/*',
            
            // Admin Subdit routes
            'min-ops/*',
            'subdit/*',
        ]);

        // Register custom middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'pawas.selected' => \App\Http\Middleware\EnsurePawasSelected::class,
        ]);

        // Redirect authenticated users away from guest routes (login, register)
        $middleware->redirectGuestsTo('/login');
        // Note: redirectUsersTo will be handled by LoginController based on role
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
