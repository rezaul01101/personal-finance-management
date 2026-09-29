<?php

use App\Http\Controllers\Api\AccountTransferController;
use App\Http\Controllers\Api\ExpenseAttachmentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\IncomeController;
use Illuminate\Support\Facades\Route;

Route::get('expenses', [ExpenseController::class, 'index']);
Route::post('expenses', [ExpenseController::class, 'store']);
Route::get('expenses/export/pdf', [ExpenseController::class, 'exportPdf']);
Route::get('expenses/{expense}', [ExpenseController::class, 'show']);
// POST alias: multipart bodies (receipt uploads) are only parsed by PHP on POST.
Route::match(['put', 'patch', 'post'], 'expenses/{expense}', [ExpenseController::class, 'update']);
Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
Route::delete('expenses/{expense}/attachments/{attachment}', [ExpenseAttachmentController::class, 'destroy']);

Route::get('incomes', [IncomeController::class, 'index']);
Route::post('incomes', [IncomeController::class, 'store']);
Route::get('incomes/{income}', [IncomeController::class, 'show']);
Route::match(['put', 'patch'], 'incomes/{income}', [IncomeController::class, 'update']);
Route::delete('incomes/{income}', [IncomeController::class, 'destroy']);

Route::get('transfers', [AccountTransferController::class, 'index']);
Route::post('transfers', [AccountTransferController::class, 'store']);
Route::get('transfers/{transfer}', [AccountTransferController::class, 'show']);
Route::match(['put', 'patch'], 'transfers/{transfer}', [AccountTransferController::class, 'update']);
Route::delete('transfers/{transfer}', [AccountTransferController::class, 'destroy']);
