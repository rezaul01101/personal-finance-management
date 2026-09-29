<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\BudgetCategoryController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\MonthlyBudgetController;
use Illuminate\Support\Facades\Route;

// Named with an `api.` prefix so they never shadow the web routes of the same resources.
Route::name('api.')->group(function () {
    Route::apiResource('accounts', AccountController::class);

    Route::get('budget-categories/{budget_category}/month', [BudgetCategoryController::class, 'month'])->name('budget-categories.month');
    Route::apiResource('budget-categories', BudgetCategoryController::class);

    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    Route::get('budgets', [MonthlyBudgetController::class, 'index'])->name('budgets.index');
    Route::put('budgets', [MonthlyBudgetController::class, 'store'])->name('budgets.store');
});
