<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // Every file in routes/api/ registers one domain's authenticated routes.
    Route::middleware('auth:sanctum')->group(function () {
        foreach (glob(__DIR__.'/api/*.php') as $routeFile) {
            require $routeFile;
        }
    });
});

Route::get('/helth', function () {
    return response()->json([
        'message' => 'Welcome to the Expense Tracking API',
        'version' => '1.0.0',
    ]);
});
