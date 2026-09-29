<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardRequest;
use App\Services\Finance\BudgetCalculator;
use App\Services\Finance\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly BudgetCalculator $budgetCalculator,
    ) {}

    public function __invoke(DashboardRequest $request): JsonResponse
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);
        $user = $request->user();

        $budgets = $this->dashboard->budgetSummaries($user, $year, $month);
        $remainingDays = $this->budgetCalculator->remainingDaysInPeriod($year, $month);

        return response()->json([
            'year' => $year,
            'month' => $month,
            'remaining_days' => $remainingDays,
            'budgets' => array_map(fn (array $row) => [
                'id' => $row['category']['id'],
                'name' => $row['category']['name'],
                'icon' => $row['category']['icon'],
                'status' => $this->status($row['summary']['usage_percentage']),
                ...collect($row['summary'])->only([
                    'budget_amount',
                    'used_amount',
                    'available_amount',
                    'is_exceeded',
                    'over_budget_amount',
                    'daily_safe_spend',
                    'usage_percentage',
                ])->all(),
            ], $budgets),
            'totals' => $this->dashboard->totals($budgets, $remainingDays),
            'top_categories' => $this->dashboard->topExpenseCategories($user, $year, $month),
        ]);
    }

    /**
     * Mirrors the web dashboard: warn from 80% used, exceeded above 100%.
     */
    private function status(float $usagePercentage): string
    {
        return match (true) {
            $usagePercentage > 100 => 'exceeded',
            $usagePercentage >= 80 => 'warning',
            default => 'healthy',
        };
    }
}
