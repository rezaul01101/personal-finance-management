<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\LoanAttachmentController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanRepaymentController;
use App\Http\Controllers\Api\LoanTransferController;
use Illuminate\Support\Facades\Route;

// Named with an `api.` prefix so they never shadow the web routes' names (contacts.index, loans.show, ...).
Route::name('api.')->group(function () {
    Route::apiResource('contacts', ContactController::class);

    // Registered before the {loan} routes so "summary" and "contacts" are not read as loan ids.
    Route::get('loans/summary', [LoanController::class, 'summary']);
    Route::get('loans/contacts', [LoanController::class, 'contacts']);
    Route::get('loans/contacts/{contact}', [LoanController::class, 'contact']);

    Route::apiResource('loans', LoanController::class);

    Route::post('loans/{loan}/attachments', [LoanAttachmentController::class, 'store']);
    Route::delete('loans/{loan}/attachments/{attachment}', [LoanAttachmentController::class, 'destroy']);

    Route::post('loans/{loan}/repayments', [LoanRepaymentController::class, 'store']);
    Route::apiResource('repayments', LoanRepaymentController::class)
        ->parameters(['repayments' => 'repayment'])
        ->only(['show', 'update', 'destroy']);

    Route::apiResource('loans.transfers', LoanTransferController::class)->except(['index']);
});
