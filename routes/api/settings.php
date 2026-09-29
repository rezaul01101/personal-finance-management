<?php

use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->group(function () {
    Route::patch('profile', [SettingsController::class, 'updateProfile']);
    Route::put('password', [SettingsController::class, 'updatePassword'])->middleware('throttle:6,1');
    Route::delete('account', [SettingsController::class, 'destroy'])->middleware('throttle:6,1');

    Route::post('backup/link', [SettingsController::class, 'backupLink'])->middleware('throttle:6,1');

    // Opened by the OS download manager without a bearer token; the signature (5 minute expiry) is the credential.
    Route::get('backup/download', [SettingsController::class, 'downloadBackup'])
        ->name('api.settings.backup.download')
        ->middleware('signed')
        ->withoutMiddleware('auth:sanctum');
});
