<?php

use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SavingsTransactionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('savings-goals', SavingsGoalController::class)->parameters(['savings-goals' => 'savings_goal'])
    ->names('api.savings-goals');

// The form requests read the bound goal/transaction, so authorise before they resolve.
Route::post('savings-goals/{savings_goal}/transactions', [SavingsTransactionController::class, 'store'])
    ->name('api.savings-goals.transactions.store')
    ->middleware('can:view,savings_goal');

Route::get('savings-transactions/{transaction}', [SavingsTransactionController::class, 'show'])->name('api.savings-transactions.show');
Route::match(['put', 'patch'], 'savings-transactions/{transaction}', [SavingsTransactionController::class, 'update'])
    ->middleware('can:update,transaction');
Route::delete('savings-transactions/{transaction}', [SavingsTransactionController::class, 'destroy']);
