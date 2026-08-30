<?php

namespace App\Http\Controllers;

use App\Enums\CategoryStatus;
use App\Http\Requests\Finance\StoreMonthlyBudgetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBudgetController extends Controller
{
    /**
     * Display the budget creation form for a given month (defaults to the
     * current month).
     */
    public function index(Request $request): Response
    {
        [$year, $month] = $this->resolvePeriod($request);

        $budgetCategories = $request->user()->budgetCategories()
            ->where('status', CategoryStatus::Active)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $monthlyBudgets = $request->user()->monthlyBudgets()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('budget_category_id');

        $periodKey = ($year * 12) + $month;

        $previousAmounts = $request->user()->monthlyBudgets()
            ->selectRaw('budget_category_id, amount')
            ->whereIn('budget_category_id', $budgetCategories->pluck('id'))
            ->whereRaw('(year * 12 + month) < ?', [$periodKey])
            ->orderByRaw('(year * 12 + month) DESC')
            ->get()
            ->unique('budget_category_id')
            ->pluck('amount', 'budget_category_id');

        $amounts = $budgetCategories->mapWithKeys(function ($category) use ($monthlyBudgets, $previousAmounts) {
            if ($monthlyBudgets->has($category->id)) {
                return [$category->id => $monthlyBudgets->get($category->id)->amount];
            }

            return [$category->id => $previousAmounts->get($category->id)];
        })->filter();

        return Inertia::render('budgets/index', [
            'year' => $year,
            'month' => $month,
            'budgetCategories' => $budgetCategories,
            'amounts' => $amounts,
            'suggestedCategoryIds' => $budgetCategories
                ->reject(fn ($category) => $monthlyBudgets->has($category->id))
                ->pluck('id')
                ->values(),
        ]);
    }

    /**
     * Save the budget amounts for every category in the given month. A
     * missing or zero amount removes any existing budget for that category
     * and month (spec §9 - budgets are opt-in per category, per month).
     */
    public function store(StoreMonthlyBudgetRequest $request): RedirectResponse
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget saved.')]);

        return to_route('budgets.index', ['year' => $validated['year'], 'month' => $validated['month']]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolvePeriod(Request $request): array
    {
        return [
            (int) $request->query('year', now()->year),
            (int) $request->query('month', now()->month),
        ];
    }
}
