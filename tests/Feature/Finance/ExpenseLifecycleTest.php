<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

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

test('creating an expense debits the account balance', function () {
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

    expect($account->fresh()->balance)->toBe('8000.00');
});

test('editing an expense reverses the old amount and applies the new one', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);
    // Simulate the debit that would have happened when the expense was created.
    $account->forceFill(['balance' => 9000])->save();

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
        ->and($account->fresh()->balance)->toBe('8500.00');
});

test('editing an expense to move it to a different account reverses the old account and debits the new one', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $oldAccount] = makeExpenseContext($user);
    $newAccount = Account::factory()->for($user)->create(['balance' => 5000]);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $oldAccount->id,
        'amount' => 1000,
    ]);
    $oldAccount->forceFill(['balance' => 9000])->save();

    $this->actingAs($user)->put(route('expenses.update', $expense), [
        'amount' => '1000',
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $newAccount->id,
        'spent_on' => '2026-08-30',
        'note' => null,
    ]);

    expect($oldAccount->fresh()->balance)->toBe('10000.00')
        ->and($newAccount->fresh()->balance)->toBe('4000.00');
});

test('deleting an expense restores the account balance', function () {
    $user = User::factory()->create();
    ['expenseCategory' => $expenseCategory, 'budgetCategory' => $budgetCategory, 'account' => $account] = makeExpenseContext($user);

    $expense = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);
    $account->forceFill(['balance' => 9000])->save();

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    expect($account->fresh()->balance)->toBe('10000.00');
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
