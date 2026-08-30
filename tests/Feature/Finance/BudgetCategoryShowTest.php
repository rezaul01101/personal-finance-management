<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $category = BudgetCategory::factory()->create();

    $this->get(route('budget-categories.show', $category))->assertRedirect(route('login'));
});

test('shows the budget summary, category breakdown, and transactions grouped by date', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create(['name' => 'Family']);
    $grocery = ExpenseCategory::factory()->for($user)->create(['name' => 'Grocery']);
    $medicine = ExpenseCategory::factory()->for($user)->create(['name' => 'Medicine']);
    $account = Account::factory()->for($user)->create();

    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    Expense::factory()->for($user)->create([
        'expense_category_id' => $grocery->id,
        'budget_category_id' => $family->id,
        'account_id' => $account->id,
        'amount' => 5200,
        'spent_on' => '2026-08-28',
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $medicine->id,
        'budget_category_id' => $family->id,
        'account_id' => $account->id,
        'amount' => 800,
        'spent_on' => '2026-08-28',
    ]);

    $this->actingAs($user)
        ->get(route('budget-categories.show', ['budget_category' => $family, 'year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('budgets/show')
            ->where('summary.used_amount', '6000.00')
            ->has('categoryBreakdown', 2)
            ->where('categoryBreakdown.0.expense_category.name', 'Grocery')
            ->where('categoryBreakdown.0.total', '5200.00')
            ->has('transactionGroups', 1)
            ->has('transactionGroups.0.expenses', 2));
});

test('a category with no budget set for the month still renders with a null summary', function () {
    $user = User::factory()->create();
    $category = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('budget-categories.show', ['budget_category' => $category, 'year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('budgets/show')
            ->where('summary', null));
});

test('a user cannot view another users budget category details', function () {
    $owner = User::factory()->create();
    $category = BudgetCategory::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('budget-categories.show', $category))
        ->assertForbidden();
});

test('expenses outside the selected month are excluded from the category details', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $family->id,
        'account_id' => $account->id,
        'amount' => 500,
        'spent_on' => '2026-07-31',
    ]);

    $this->actingAs($user)
        ->get(route('budget-categories.show', ['budget_category' => $family, 'year' => 2026, 'month' => 8]))
        ->assertInertia(fn ($page) => $page->has('transactionGroups', 0));
});
