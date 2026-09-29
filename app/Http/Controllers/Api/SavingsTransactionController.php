<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\Finance\InsufficientSavingsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSavingsTransactionRequest;
use App\Http\Requests\Finance\UpdateSavingsTransactionRequest;
use App\Http\Resources\SavingsTransactionResource;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Services\Finance\SavingsTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SavingsTransactionController extends Controller
{
    public function __construct(private readonly SavingsTransactionService $transactions) {}

    public function store(StoreSavingsTransactionRequest $request, SavingsGoal $savingsGoal): JsonResponse
    {
        Gate::authorize('view', $savingsGoal);

        $transaction = $this->guarded(fn () => $this->transactions->create($request->user(), [
            ...$request->validated(),
            'savings_goal_id' => $savingsGoal->id,
        ]));

        return (new SavingsTransactionResource($transaction->load('account:id,name')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SavingsTransaction $transaction): SavingsTransactionResource
    {
        Gate::authorize('view', $transaction);

        return new SavingsTransactionResource($transaction->load('account:id,name'));
    }

    public function update(UpdateSavingsTransactionRequest $request, SavingsTransaction $transaction): SavingsTransactionResource
    {
        Gate::authorize('update', $transaction);

        $this->guarded(fn () => $this->transactions->update($transaction, $request->validated()));

        return new SavingsTransactionResource($transaction->refresh()->load('account:id,name'));
    }

    public function destroy(SavingsTransaction $transaction): Response
    {
        Gate::authorize('delete', $transaction);

        $this->guarded(fn () => $this->transactions->delete($transaction));

        return response()->noContent();
    }

    /**
     * Surface a would-be negative balance as a 422 on the amount field, like the web app.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function guarded(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (InsufficientSavingsException $exception) {
            throw ValidationException::withMessages(['amount' => [$exception->getMessage()]]);
        }
    }
}
