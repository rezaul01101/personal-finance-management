<?php

namespace App\Http\Controllers;

use App\Services\Finance\BudgetCalculator;
use App\Services\Finance\DashboardService;
use App\Services\Finance\LoanCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly BudgetCalculator $budgetCalculator,
        private readonly LoanCalculator $loanCalculator,
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
            'loanSummary' => $this->loanCalculator->dashboardSummary($user)->toArray(),
            'hasLoans' => $user->loans()->exists(),
        ]);
    }
}
