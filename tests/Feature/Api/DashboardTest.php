<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Sanctum\Sanctum;

function createBudgetedSpend(User $user, string $name, int $budget, int $spent, ?string $expenseCategoryName = null): void
{
    $budgetCategory = BudgetCategory::factory()->for($user)->create(['name' => $name]);
    $expenseCategory = ExpenseCategory::factory()->for($user)->create(['name' => $expenseCategoryName ?? "{$name} spend"]);
    $account = Account::factory()->for($user)->create();

    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $budgetCategory->id,
        'year' => 2026,
        'month' => 8,
        'amount' => $budget,
    ]);

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => $spent,
        'spent_on' => '2026-08-15',
    ]);
}

afterEach(fn () => CarbonImmutable::setTestNow());

test('guests cannot read the dashboard', function () {
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
});

test('the dashboard returns budgets, totals and top categories for the month', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 8, 30));
    $user = User::factory()->create();
    createBudgetedSpend($user, 'Family', 15000, 12400, 'Groceries');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard?year=2026&month=8')
        ->assertOk()
        ->assertJsonPath('year', 2026)
        ->assertJsonPath('month', 8)
        ->assertJsonPath('remaining_days', 2)
        ->assertJsonCount(1, 'budgets')
        ->assertJsonPath('budgets.0.name', 'Family')
        ->assertJsonPath('budgets.0.used_amount', '12400.00')
        ->assertJsonPath('budgets.0.available_amount', '2600.00')
        ->assertJsonPath('budgets.0.daily_safe_spend', '1300.00')
        ->assertJsonPath('budgets.0.status', 'warning')
        ->assertJsonPath('totals.total_budget', '15000.00')
        ->assertJsonPath('top_categories.0.id', ExpenseCategory::query()->where('name', 'Groceries')->value('id'))
        ->assertJsonPath('top_categories.0.label', 'Groceries')
        ->assertJsonPath('top_categories.0.amount', '12400.00');
});

test('budget status follows the warning and exceeded thresholds', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 8, 30));
    $user = User::factory()->create();
    createBudgetedSpend($user, 'A Healthy', 1000, 799);
    createBudgetedSpend($user, 'B Warning', 1000, 800);
    createBudgetedSpend($user, 'C Full', 1000, 1000);
    createBudgetedSpend($user, 'D Exceeded', 1000, 1001);

    Sanctum::actingAs($user);

    $statuses = collect($this->getJson('/api/v1/dashboard?year=2026&month=8')->assertOk()->json('budgets'))
        ->pluck('status', 'name')
        ->all();

    expect($statuses)->toBe([
        'A Healthy' => 'healthy',
        'B Warning' => 'warning',
        'C Full' => 'warning',
        'D Exceeded' => 'exceeded',
    ]);
});

test('the dashboard only includes the authenticated users data', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 8, 30));
    $user = User::factory()->create();
    createBudgetedSpend(User::factory()->create(), 'Someone Elses', 5000, 100);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/dashboard?year=2026&month=8')
        ->assertOk()
        ->assertJsonCount(0, 'budgets')
        ->assertJsonCount(0, 'top_categories');
});

test('the dashboard defaults to the current month', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 9, 29));
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('year', 2026)
        ->assertJsonPath('month', 9)
        ->assertJsonPath('remaining_days', 2);
});

test('the dashboard validates the month and year', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/dashboard?year=1800&month=13')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['year', 'month']);
});
