<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardRequest;
use App\Http\Requests\Finance\StoreMonthlyBudgetRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MonthlyBudgetController extends Controller
{
    /**
     * Every active budget category with its amount for the month. Categories
     * without a budget yet carry over their most recent earlier amount.
     */
    public function index(DashboardRequest $request): JsonResponse
    {
        return $this->payload(
            $request->user(),
            $request->integer('year', now()->year),
            $request->integer('month', now()->month),
        );
    }

    /**
     * Save the amounts for every listed category in the month. A missing or
     * zero amount removes any existing budget for that category and month.
     */
    public function store(StoreMonthlyBudgetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated) {
            foreach ($validated['budgets'] as $row) {
                $amount = $row['amount'] ?? null;

                if ($amount === null || (float) $amount <= 0) {
                    $request->user()->monthlyBudgets()
                        ->where('budget_category_id', $row['budget_category_id'])
                        ->where('year', $validated['year'])
                        ->where('month', $validated['month'])
                        ->delete();

                    continue;
                }

                $request->user()->monthlyBudgets()->updateOrCreate(
                    [
                        'budget_category_id' => $row['budget_category_id'],
                        'year' => $validated['year'],
                        'month' => $validated['month'],
                    ],
                    ['amount' => $amount],
                );
            }
        });

        return $this->payload($request->user(), (int) $validated['year'], (int) $validated['month']);
    }

    private function payload(User $user, int $year, int $month): JsonResponse
    {
        $budgetCategories = $user->budgetCategories()
            ->where('status', CategoryStatus::Active)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $monthlyBudgets = $user->monthlyBudgets()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('budget_category_id');

        $previousAmounts = $user->monthlyBudgets()
            ->selectRaw('budget_category_id, amount')
            ->whereIn('budget_category_id', $budgetCategories->pluck('id'))
            ->whereRaw('(year * 12 + month) < ?', [($year * 12) + $month])
            ->orderByRaw('(year * 12 + month) DESC')
            ->get()
            ->unique('budget_category_id')
            ->pluck('amount', 'budget_category_id');

        return response()->json(['data' => [
            'year' => $year,
            'month' => $month,
            'categories' => $budgetCategories->map(function ($category) use ($monthlyBudgets, $previousAmounts) {
                $saved = $monthlyBudgets->get($category->id);
                $amount = $saved?->amount ?? $previousAmounts->get($category->id);

                return [
                    'budget_category_id' => $category->id,
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'amount' => $amount === null ? null : number_format((float) $amount, 2, '.', ''),
                    'carried_over' => $saved === null && $amount !== null,
                ];
            })->values(),
        ]]);
    }
}
