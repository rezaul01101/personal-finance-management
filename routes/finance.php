<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTransferController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\ExpenseAttachmentController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\MonthlyBudgetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('accounts', AccountController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('budget-categories', BudgetCategoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('budgets', [MonthlyBudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [MonthlyBudgetController::class, 'store'])->name('budgets.store');

    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::delete('expenses/{expense}/attachments/{attachment}', [ExpenseAttachmentController::class, 'destroy'])
        ->name('expenses.attachments.destroy');

    Route::resource('incomes', IncomeController::class)->except(['show']);

    Route::resource('transfers', AccountTransferController::class)->except(['show']);
});
