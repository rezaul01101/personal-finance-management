<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Finance\AccountCalculator;

function makeExpenseContext(User $user): array
{
    return [
        'expenseCategory' => ExpenseCategory::factory()->for($user)->create(),
        'budgetCategory' => BudgetCategory::factory()->for($user)->create(),
        'account' => Account::factory()->for($user)->create(['balance' => 10000]),
    ];
}

test('guests are redirected to the login page', function () {
    $this->get(route('expenses.index'))->assertRedirect(route('login'));
});

test('creating an expense does not change the account balance, but debits the computed current balance', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => '2000',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $account->id,
            'spent_on' => '2026-08-30',
            'note' => 'Lunch',
        ])
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'amount' => '2000.00',
        'note' => 'Lunch',
    ]);

    expect($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('8000.00');
});

test('editing an expense does not change the account balance, but the computed current balance reflects the new amount', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), [
            'amount' => '1500',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $account->id,
            'spent_on' => '2026-08-30',
            'note' => null,
        ])
        ->assertRedirect(route('expenses.index'));

    expect($expense->fresh()->amount)->toBe('1500.00')
        ->and($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('8500.00');
});

test('editing an expense to move it to a different account does not change either account balance, but debits the new account and credits the old one in the computed totals', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $oldAccount] = makeExpenseContext($user);
    $newAccount = Account::factory()->for($user)->create(['balance' => 5000]);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $oldAccount->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user)->put(route('expenses.update', $expense), [
        'amount' => '1000',
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $newAccount->id,
        'spent_on' => '2026-08-30',
        'note' => null,
    ]);

    $calculator = app(AccountCalculator::class);

    expect($oldAccount->fresh()->balance)->toBe('10000.00')
        ->and($newAccount->fresh()->balance)->toBe('5000.00')
        ->and($calculator->currentBalance($oldAccount->fresh())->toDecimalString())->toBe('10000.00')
        ->and($calculator->currentBalance($newAccount->fresh())->toDecimalString())->toBe('4000.00');
});

test('deleting an expense does not change the account balance, and it stops affecting the computed current balance', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    expect($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('10000.00');
});

test('an expense can be created that exceeds the accounts remaining budget - budgets are a soft limit', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => '999999',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $account->id,
            'spent_on' => '2026-08-30',
        ])
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'amount' => '999999.00']);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => '0',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $account->id,
            'spent_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('a user cannot attach another users account/category to their own expense', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);
    $strangerAccount = Account::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => '100',
            'expense_category_id' => $expenseCategory->id,
            'budget_category_id' => $budgetCategory->id,
            'account_id' => $strangerAccount->id,
            'spent_on' => '2026-08-30',
        ])
        ->assertInvalid(['account_id']);
});

test('a user cannot edit or delete another users expense', function () {
    $owner = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($owner);
    $expense = Expense::factory()->for($owner)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('expenses.update', $expense), ['amount' => '1'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('expenses.destroy', $expense))
        ->assertForbidden();
});
