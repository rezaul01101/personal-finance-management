<?php

namespace App\Http\Controllers;

use App\Services\Finance\BudgetCalculator;
use App\Services\Finance\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly BudgetCalculator $budgetCalculator,
    ) {}

    public function __invoke(Request $request): Response
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $user = $request->user();

        $budgets = $this->dashboard->budgetSummaries($user, $year, $month);
        $remainingDays = $this->budgetCalculator->remainingDaysInPeriod($year, $month);

        return Inertia::render('dashboard', [
            'year' => $year,
            'month' => $month,
            'remainingDays' => $remainingDays,
            'budgets' => $budgets,
            'totals' => $this->dashboard->totals($budgets, $remainingDays),
            'topExpenseCategories' => $this->dashboard->topExpenseCategories($user, $year, $month),
            'recentExpenses' => $this->dashboard->recentExpenses($user, $year, $month),
        ]);
    }
}
