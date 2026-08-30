<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\User;
use Carbon\CarbonImmutable;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the spec example: 15,000 budget, 12,400 used, 2 days left, 1,300 daily safe spend', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 8, 30));

    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create(['name' => 'Family']);
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $family->id,
        'account_id' => $account->id,
        'amount' => 12400,
        'spent_on' => '2026-08-15',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('remainingDays', 2)
            ->has('budgets', 1)
            ->where('budgets.0.category.name', 'Family')
            ->where('budgets.0.summary.used_amount', '12400.00')
            ->where('budgets.0.summary.available_amount', '2600.00')
            ->where('budgets.0.summary.daily_safe_spend', '1300.00')
            ->where('totals.total_budget', '15000.00')
            ->where('totals.total_used', '12400.00'));

    CarbonImmutable::setTestNow();
});

test('a budget category with no monthly budget for the selected month does not appear as a card', function () {
    $user = User::factory()->create();
    BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('budgets', 0));
});

test('navigating to a different month changes the dashboard payload', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();

    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 7,
        'amount' => 12000,
    ]);
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['year' => 2026, 'month' => 7]))
        ->assertInertia(fn ($page) => $page->where('budgets.0.summary.budget_amount', '12000.00'));

    $this->actingAs($user)
        ->get(route('dashboard', ['year' => 2026, 'month' => 8]))
        ->assertInertia(fn ($page) => $page->where('budgets.0.summary.budget_amount', '15000.00'));
});

test('the top expense categories panel ranks by spend, highest first', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $food = ExpenseCategory::factory()->for($user)->create(['name' => 'Food']);
    $transport = ExpenseCategory::factory()->for($user)->create(['name' => 'Transport']);
    $account = Account::factory()->for($user)->create();

    Expense::factory()->for($user)->create([
        'expense_category_id' => $transport->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 1000,
        'spent_on' => now()->toDateString(),
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $food->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 4000,
        'spent_on' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('topExpenseCategories.0.label', 'Food')
            ->where('topExpenseCategories.0.amount', '4000.00'));
});

test('recent expenses are limited and ordered most recent first', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 100,
        'spent_on' => now()->subDays(2)->toDateString(),
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 200,
        'spent_on' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('recentExpenses', 2)
            ->where('recentExpenses.0.amount', '200.00'));
});
