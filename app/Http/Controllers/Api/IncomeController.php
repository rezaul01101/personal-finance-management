<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreIncomeRequest;
use App\Http\Requests\Finance\UpdateIncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Models\Income;
use App\Services\Finance\IncomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class IncomeController extends Controller
{
    public function __construct(private readonly IncomeService $incomes) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $incomes = $request->user()->incomes()
            ->with('account')
            ->orderByDesc('received_on')
            ->orderByDesc('id')
            ->paginate(20);

        return IncomeResource::collection($incomes);
    }

    public function show(Income $income): IncomeResource
    {
        Gate::authorize('view', $income);

        return new IncomeResource($income->load('account'));
    }

    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $income = $this->incomes->create($request->user(), $request->validated());

        return (new IncomeResource($income->load('account')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateIncomeRequest $request, Income $income): IncomeResource
    {
        Gate::authorize('update', $income);

        $income = $this->incomes->update($income, $request->validated());

        return new IncomeResource($income->load('account'));
    }

    public function destroy(Income $income): Response
    {
        Gate::authorize('delete', $income);

        $this->incomes->delete($income);

        return response()->noContent();
    }
}
