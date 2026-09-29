<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseCategoryRequest;
use App\Http\Requests\Finance\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ExpenseCategoryResource::collection(
            $request->user()->expenseCategories()->orderBy('sort_order')->orderBy('name')->get(),
        );
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $category = $request->user()->expenseCategories()->create($request->validated())->refresh();

        return (new ExpenseCategoryResource($category))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'expense_category')]
    public function show(ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        return new ExpenseCategoryResource($expenseCategory);
    }

    #[Authorize('update', 'expense_category')]
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): ExpenseCategoryResource
    {
        $expenseCategory->update($request->validated());

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * Remove the expense category, unless expenses still reference it.
     */
    #[Authorize('delete', 'expense_category')]
    public function destroy(ExpenseCategory $expenseCategory): Response|JsonResponse
    {
        try {
            $expenseCategory->delete();
        } catch (QueryException) {
            return response()->json([
                'message' => 'This category has expenses recorded against it and cannot be deleted. Archive it instead.',
            ], 409);
        }

        return response()->noContent();
    }
}
