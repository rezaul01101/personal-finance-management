<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreExpenseCategoryRequest;
use App\Http\Requests\Finance\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the user's expense categories.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('expense-categories/index', [
            'expenseCategories' => $request->user()->expenseCategories()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created expense category.
     */
    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        $request->user()->expenseCategories()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category created.')]);

        return to_route('expense-categories.index');
    }

    /**
     * Update the expense category.
     */
    #[Authorize('update', 'expense_category')]
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category updated.')]);

        return to_route('expense-categories.index');
    }

    /**
     * Remove the expense category, unless expenses still reference it.
     */
    #[Authorize('delete', 'expense_category')]
    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        try {
            $expenseCategory->delete();
        } catch (QueryException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This category has expenses recorded against it and cannot be deleted. Archive it instead.')]);

            return to_route('expense-categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expense category deleted.')]);

        return to_route('expense-categories.index');
    }
}
