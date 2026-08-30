<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreBudgetCategoryRequest;
use App\Http\Requests\Finance\UpdateBudgetCategoryRequest;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\MonthlyBudget;
use App\Services\Finance\BudgetCalculator;
use App\Services\Finance\BudgetMath;
use App\Services\Finance\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BudgetCategoryController extends Controller
{
    public function __construct(private readonly BudgetCalculator $budgetCalculator) {}

    /**
     * Display a listing of the user's budget categories.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('budget-categories/index', [
            'budgetCategories' => $request->user()->budgetCategories()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created budget category.
     */
    public function store(StoreBudgetCategoryRequest $request): RedirectResponse
    {
        $request->user()->budgetCategories()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget category created.')]);

        return to_route('budget-categories.index');
    }

    /**
     * Show the category details page: the month's budget health, a spend
     * breakdown by expense category, and the category's transactions
     * grouped by date (spec §10-11).
     */
    #[Authorize('view', 'budget_category')]
    public function show(Request $request, BudgetCategory $budgetCategory): Response
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $monthlyBudget = MonthlyBudget::query()
            ->where('user_id', $request->user()->id)
            ->where('budget_category_id', $budgetCategory->id)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        $summary = null;
        if ($monthlyBudget) {
            $monthlyBudget->setRelation('budgetCategory', $budgetCategory);
            $summary = $this->budgetCalculator->summarize($monthlyBudget)->toArray();
        }

        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        $expenses = Expense::query()
            ->where('user_id', $request->user()->id)
            ->where('budget_category_id', $budgetCategory->id)
            ->whereBetween('spent_on', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with(['expenseCategory', 'account'])
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('budgets/show', [
            'year' => $year,
            'month' => $month,
            'budgetCategory' => $budgetCategory,
            'summary' => $summary,
            'categoryBreakdown' => $this->categoryBreakdown($expenses),
            'transactionGroups' => $this->groupByDate($expenses),
        ]);
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array<int, array{expense_category: array<string, mixed>, total: string, percentage: float}>
     */
    private function categoryBreakdown(Collection $expenses): array
    {
        $totalUsed = $expenses->reduce(
            fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
            Money::zero(),
        );

        return $expenses
            ->groupBy('expense_category_id')
            ->map(function (Collection $group) use ($totalUsed) {
                $categoryTotal = $group->reduce(
                    fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
                    Money::zero(),
                );

                return [
                    'expense_category' => [
                        'id' => $group->first()->expenseCategory->id,
                        'name' => $group->first()->expenseCategory->name,
                        'icon' => $group->first()->expenseCategory->icon,
                    ],
                    'total' => $categoryTotal->toDecimalString(),
                    'percentage' => BudgetMath::usagePercentage($totalUsed, $categoryTotal),
                    'sortKey' => $categoryTotal->toFloat(),
                ];
            })
            ->sortByDesc('sortKey')
            ->map(fn (array $row) => [
                'expense_category' => $row['expense_category'],
                'total' => $row['total'],
                'percentage' => $row['percentage'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Expense>  $expenses
     * @return array<int, array{date: string, label: string, expenses: array<int, Expense>}>
     */
    private function groupByDate(Collection $expenses): array
    {
        $today = CarbonImmutable::today();

        return $expenses
            ->groupBy(fn (Expense $expense) => $expense->spent_on->format('Y-m-d'))
            ->map(function (Collection $group, string $date) use ($today) {
                $day = CarbonImmutable::parse($date);

                $label = match (true) {
                    $day->isSameDay($today) => 'Today',
                    $day->isSameDay($today->subDay()) => 'Yesterday',
                    default => $day->format('j F'),
                };

                return [
                    'date' => $date,
                    'label' => $label,
                    'expenses' => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Update the budget category.
     */
    #[Authorize('update', 'budget_category')]
    public function update(UpdateBudgetCategoryRequest $request, BudgetCategory $budgetCategory): RedirectResponse
    {
        $budgetCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget category updated.')]);

        return to_route('budget-categories.index');
    }

    /**
     * Remove the budget category.
     */
    #[Authorize('delete', 'budget_category')]
    public function destroy(BudgetCategory $budgetCategory): RedirectResponse
    {
        $budgetCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Budget category deleted.')]);

        return to_route('budget-categories.index');
    }
}
