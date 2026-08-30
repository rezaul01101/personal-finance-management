<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MonthlyBudget;
use App\Models\User;
use App\Services\Finance\BudgetCalculator;
use Carbon\CarbonImmutable;

test('the home screen example from the spec: 15,000 budget, 12,400 used, 2 days left, 1,300 daily safe spend', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create(['name' => 'Family']);
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    $monthlyBudget = MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $budgetCategory->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 12400,
        'spent_on' => '2026-08-15',
    ]);

    $summary = app(BudgetCalculator::class)->summarize($monthlyBudget, CarbonImmutable::create(2026, 8, 30));

    expect($summary->usedAmount->toDecimalString())->toBe('12400.00')
        ->and($summary->availableAmount->toDecimalString())->toBe('2600.00')
        ->and($summary->isExceeded)->toBeFalse()
        ->and($summary->remainingDays)->toBe(2)
        ->and($summary->dailySafeSpend->toDecimalString())->toBe('1300.00');
});

test('expenses outside the budgeted month or a different category are excluded from the sum', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $otherBudgetCategory = BudgetCategory::factory()->for($user)->create();
    $expenseCategory = ExpenseCategory::factory()->for($user)->create();
    $account = Account::factory()->for($user)->create();

    $monthlyBudget = MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $budgetCategory->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    // In the right category but the wrong month.
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 5000,
        'spent_on' => '2026-07-31',
    ]);

    // In the right month but a different budget category.
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $otherBudgetCategory->id,
        'account_id' => $account->id,
        'amount' => 3000,
        'spent_on' => '2026-08-10',
    ]);

    $summary = app(BudgetCalculator::class)->summarize($monthlyBudget, CarbonImmutable::create(2026, 8, 30));

    expect($summary->usedAmount->toDecimalString())->toBe('0.00');
});

test('remaining days is zero for a past month', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $monthlyBudget = MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $budgetCategory->id,
        'year' => 2026,
        'month' => 7,
        'amount' => 15000,
    ]);

    $summary = app(BudgetCalculator::class)->summarize($monthlyBudget, CarbonImmutable::create(2026, 8, 30));

    expect($summary->remainingDays)->toBe(0)
        ->and($summary->dailySafeSpend->toDecimalString())->toBe('0.00');
});

test('remaining days is the full month length for a future month', function () {
    $user = User::factory()->create();
    $budgetCategory = BudgetCategory::factory()->for($user)->create();
    $monthlyBudget = MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $budgetCategory->id,
        'year' => 2026,
        'month' => 9,
        'amount' => 15000,
    ]);

    $summary = app(BudgetCalculator::class)->summarize($monthlyBudget, CarbonImmutable::create(2026, 8, 30));

    expect($summary->remainingDays)->toBe(30);
});
