<?php

namespace App\Services\Finance;

use App\Enums\CategoryStatus;
use App\Models\Expense;
use App\Models\MonthlyBudget;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class DashboardService
{
    public function __construct(private readonly BudgetCalculator $budgetCalculator) {}

    /**
     * Per-category budget summaries for every active budget category that
     * has a budget defined for the given month (spec §9 - no budget for a
     * category/month means no card, not a zero-budget card).
     *
     * @return array<int, array{category: array<string, mixed>, summary: array<string, mixed>}>
     */
    public function budgetSummaries(User $user, int $year, int $month): array
    {
        $budgetCategories = $user->budgetCategories()
            ->where('status', CategoryStatus::Active)
            ->get()
            ->keyBy('id');

        return $user->monthlyBudgets()
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->filter(fn (MonthlyBudget $monthlyBudget) => $budgetCategories->has($monthlyBudget->budget_category_id))
            ->map(function (MonthlyBudget $monthlyBudget) use ($budgetCategories) {
                $category = $budgetCategories->get($monthlyBudget->budget_category_id);
                $monthlyBudget->setRelation('budgetCategory', $category);

                return [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                    ],
                    'summary' => $this->budgetCalculator->summarize($monthlyBudget)->toArray(),
                ];
            })
            ->sortBy('category.name')
            ->values()
            ->all();
    }

    /**
     * Aggregate totals across every budgeted category, for the KPI strip.
     *
     * @param  array<int, array{category: array<string, mixed>, summary: array<string, mixed>}>  $budgetSummaries
     * @return array<string, mixed>
     */
    public function totals(array $budgetSummaries, int $remainingDays): array
    {
        $totalBudget = Money::zero();
        $totalUsed = Money::zero();

        foreach ($budgetSummaries as $row) {
            $totalBudget = $totalBudget->add(Money::of($row['summary']['budget_amount']));
            $totalUsed = $totalUsed->add(Money::of($row['summary']['used_amount']));
        }

        $totalAvailable = BudgetMath::available($totalBudget, $totalUsed);

        return [
            'total_budget' => $totalBudget->toDecimalString(),
            'total_used' => $totalUsed->toDecimalString(),
            'total_available' => $totalAvailable->toDecimalString(),
            'is_exceeded' => BudgetMath::isExceeded($totalBudget, $totalUsed),
            'daily_safe_spend' => BudgetMath::dailySafeSpend($totalAvailable, $remainingDays)->toDecimalString(),
            'usage_percentage' => BudgetMath::usagePercentage($totalBudget, $totalUsed),
        ];
    }

    /**
     * Spend by expense category across all budgets for the month, for the
     * "top categories" panel - highest spend first.
     *
     * @return array<int, array{label: string, amount: string, percentage: float}>
     */
    public function topExpenseCategories(User $user, int $year, int $month, int $limit = 5): array
    {
        $expenses = $this->expensesForPeriod($user, $year, $month, ['expenseCategory']);

        $totalSpent = $expenses->reduce(
            fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
            Money::zero(),
        );

        return $expenses
            ->groupBy('expense_category_id')
            ->map(function (Collection $group) use ($totalSpent) {
                $categoryTotal = $group->reduce(
                    fn (Money $carry, Expense $expense) => $carry->add(Money::of($expense->amount)),
                    Money::zero(),
                );

                return [
                    'label' => $group->first()->expenseCategory->name,
                    'amount' => $categoryTotal->toDecimalString(),
                    'percentage' => BudgetMath::usagePercentage($totalSpent, $categoryTotal),
                    'total' => $categoryTotal,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['total']->toFloat())
            ->take($limit)
            ->map(fn (array $row) => [
                'label' => $row['label'],
                'amount' => $row['amount'],
                'percentage' => $row['percentage'],
            ])
            ->values()
            ->all();
    }

    /**
     * The most recent expenses in the month, for the dashboard preview list.
     *
     * @return Collection<int, Expense>
     */
    public function recentExpenses(User $user, int $year, int $month, int $limit = 6): Collection
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        return Expense::query()
            ->where('user_id', $user->id)
            ->whereBetween('spent_on', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with(['expenseCategory', 'budgetCategory', 'account'])
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, string>  $with
     * @return Collection<int, Expense>
     */
    private function expensesForPeriod(User $user, int $year, int $month, array $with): Collection
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        return Expense::query()
            ->where('user_id', $user->id)
            ->whereBetween('spent_on', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with($with)
            ->get();
    }
}
