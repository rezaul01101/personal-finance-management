<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot use the budgets api', function () {
    $this->getJson('/api/v1/budgets')->assertUnauthorized();
    $this->putJson('/api/v1/budgets', [])->assertUnauthorized();
});

test('the month lists active categories and carries over the latest earlier amount', function () {
    $user = User::factory()->create();
    $food = BudgetCategory::factory()->for($user)->create(['name' => 'Food']);
    $travel = BudgetCategory::factory()->for($user)->create(['name' => 'Travel']);
    BudgetCategory::factory()->for($user)->archived()->create(['name' => 'Old']);
    BudgetCategory::factory()->create(['name' => 'Other user']);
    MonthlyBudget::factory()->for($user)->create(['budget_category_id' => $food->id, 'year' => 2026, 'month' => 6, 'amount' => 500]);
    MonthlyBudget::factory()->for($user)->create(['budget_category_id' => $food->id, 'year' => 2026, 'month' => 8, 'amount' => 700]);
    MonthlyBudget::factory()->for($user)->create(['budget_category_id' => $travel->id, 'year' => 2026, 'month' => 7, 'amount' => 900]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/budgets?year=2026&month=8')
        ->assertOk()
        ->assertJsonCount(2, 'data.categories')
        ->assertJsonPath('data.categories.0.name', 'Food')
        ->assertJsonPath('data.categories.0.amount', '700.00')
        ->assertJsonPath('data.categories.0.carried_over', false)
        ->assertJsonPath('data.categories.1.amount', '900.00')
        ->assertJsonPath('data.categories.1.carried_over', true);

    $this->getJson('/api/v1/budgets?year=2026&month=5')
        ->assertJsonPath('data.categories.0.amount', null);
});

test('saving sets, updates and removes budgets for the month', function () {
    $user = User::factory()->create();
    $food = BudgetCategory::factory()->for($user)->create(['name' => 'Food']);
    $travel = BudgetCategory::factory()->for($user)->create(['name' => 'Travel']);
    MonthlyBudget::factory()->for($user)->create(['budget_category_id' => $travel->id, 'year' => 2026, 'month' => 8, 'amount' => 900]);
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/budgets', [
        'year' => 2026,
        'month' => 8,
        'budgets' => [
            ['budget_category_id' => $food->id, 'amount' => '1200.5'],
            ['budget_category_id' => $travel->id, 'amount' => 0],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.categories.0.amount', '1200.50')
        ->assertJsonPath('data.categories.0.carried_over', false);

    $this->assertDatabaseHas('monthly_budgets', ['budget_category_id' => $food->id, 'month' => 8, 'amount' => 1200.5]);
    $this->assertDatabaseMissing('monthly_budgets', ['budget_category_id' => $travel->id, 'month' => 8]);
});

test('saving validates the payload and rejects other users categories', function () {
    $user = User::factory()->create();
    $foreign = BudgetCategory::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/budgets', [])->assertUnprocessable()->assertJsonValidationErrors(['year', 'month', 'budgets']);
    $this->putJson('/api/v1/budgets', [
        'year' => 2026,
        'month' => 8,
        'budgets' => [['budget_category_id' => $foreign->id, 'amount' => -5]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['budgets.0.budget_category_id', 'budgets.0.amount']);
});

test('the category month view returns summary, breakdown and grouped expenses', function () {
    $user = User::factory()->create();
    $category = BudgetCategory::factory()->for($user)->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create(['name' => 'Food']);
    MonthlyBudget::factory()->for($user)->create(['budget_category_id' => $category->id, 'year' => 2026, 'month' => 8, 'amount' => 1000]);
    Expense::factory()->for($user)->create([
        'budget_category_id' => $category->id,
        'expense_category_id' => $expenseCategory->id,
        'account_id' => Account::factory()->for($user)->create()->id,
        'amount' => 250,
        'spent_on' => '2026-08-10',
    ]);
    Expense::factory()->for($user)->create(['spent_on' => '2026-08-11']);
    Sanctum::actingAs($user);

    $this->getJson("/api/v1/budget-categories/{$category->id}/month?year=2026&month=8")
        ->assertOk()
        ->assertJsonPath('data.summary.budget_amount', '1000.00')
        ->assertJsonPath('data.summary.used_amount', '250.00')
        ->assertJsonPath('data.category_breakdown.0.expense_category.name', 'Food')
        ->assertJsonCount(1, 'data.transaction_groups')
        ->assertJsonPath('data.transaction_groups.0.expenses.0.amount', '250.00');

    $this->getJson("/api/v1/budget-categories/{$category->id}/month?year=2026&month=9")
        ->assertOk()
        ->assertJsonPath('data.summary', null)
        ->assertJsonPath('data.transaction_groups', []);

    $this->getJson("/api/v1/budget-categories/{$category->id}/month?month=13")->assertUnprocessable();
});
