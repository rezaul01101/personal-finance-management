<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreSavingsGoalRequest;
use App\Http\Requests\Finance\UpdateSavingsGoalRequest;
use App\Http\Resources\SavingsGoalResource;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SavingsGoalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return SavingsGoalResource::collection(
            $request->user()->savingsGoals()->orderByDesc('created_at')->orderByDesc('id')->get(),
        );
    }

    public function store(StoreSavingsGoalRequest $request): JsonResponse
    {
        $goal = $request->user()->savingsGoals()->create($request->validated());

        return (new SavingsGoalResource($goal->refresh()))->response()->setStatusCode(201);
    }

    public function show(SavingsGoal $savingsGoal): SavingsGoalResource
    {
        Gate::authorize('view', $savingsGoal);

        $savingsGoal->load(['transactions' => fn ($query) => $query
            ->with('account:id,name')
            ->orderByDesc('transacted_on')
            ->orderByDesc('id')]);

        return new SavingsGoalResource($savingsGoal);
    }

    public function update(UpdateSavingsGoalRequest $request, SavingsGoal $savingsGoal): SavingsGoalResource
    {
        Gate::authorize('update', $savingsGoal);

        $savingsGoal->update($request->validated());

        return new SavingsGoalResource($savingsGoal->refresh());
    }

    public function destroy(SavingsGoal $savingsGoal): Response
    {
        Gate::authorize('delete', $savingsGoal);

        $savingsGoal->delete();

        return response()->noContent();
    }
}
