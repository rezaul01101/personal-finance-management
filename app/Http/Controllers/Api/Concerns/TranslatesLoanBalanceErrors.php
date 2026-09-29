<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Exceptions\Finance\InsufficientLoanBalanceException;
use App\Exceptions\Finance\InsufficientLoanHoldingBalanceException;
use Closure;
use Illuminate\Validation\ValidationException;

trait TranslatesLoanBalanceErrors
{
    /**
     * The web app renders these exceptions as `back()->withErrors(['amount' => ...])`;
     * for the API they become a standard 422 with `errors.amount`.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function withBalanceErrors(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (InsufficientLoanBalanceException|InsufficientLoanHoldingBalanceException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }
    }
}
