<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', HomeController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/finance.php';
