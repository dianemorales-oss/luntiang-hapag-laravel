<?php
// bootstrap/app.php (Laravel 12)
// Add throttle to auth routes

use App\Http\Middleware\CustomerAuth;
use App\Http\Middleware\AdminAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'customer.auth' => CustomerAuth::class,
            'admin.auth' => AdminAuth::class,
        ]);
        // Throttle login
        $middleware->throttleApi(); // optional
    })
    ->withExceptions(...)->create();

// In routes/web.php:
Route::post('/login', [AuthController::class,'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/register', [AuthController::class,'register'])->middleware('throttle:3,1')->name('register.submit');
