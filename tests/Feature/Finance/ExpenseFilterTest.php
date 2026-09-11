<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

function expenseFilterFixtures(User $user): array
{
    return [
        'account' => Account::factory()->for($user)->create(),
        'budgetCategory' => BudgetCategory::factory()->for($user)->create(),
        'expenseCategory' => ExpenseCategory::factory()->for($user)->create(),
    ];
}

test('guests are redirected to the login page for the pdf export', function () {
    $this->get(route('expenses.export.pdf'))->assertRedirect(route('login'));
});

test('the index filters expenses by category', function () {
    $user = User::factory()->create();
    ['account' => $account, 'budgetCategory' => $budgetCategory] = expenseFilterFixtures($user);
    $groceries = ExpenseCategory::factory()->for($user)->create();
    $transport = ExpenseCategory::factory()->for($user)->create();

    $match = Expense::factory()->for($user)->create([
        'expense_category_id' => $groceries->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $transport->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index', ['expense_category_id' => $groceries->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('expenses/index')
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $match->id));
});

test('the index filters expenses by budget category', function () {
    $user = User::factory()->create();
    ['account' => $account, 'expenseCategory' => $expenseCategory] = expenseFilterFixtures($user);
    $family = BudgetCategory::factory()->for($user)->create();
    $personal = BudgetCategory::factory()->for($user)->create();

    $match = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $family->id,
        'account_id' => $account->id,
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $personal->id,
        'account_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index', ['budget_category_id' => $family->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $match->id));
});

test('the index filters expenses by account', function () {
    $user = User::factory()->create();
    ['budgetCategory' => $budgetCategory, 'expenseCategory' => $expenseCategory] = expenseFilterFixtures($user);
    $cash = Account::factory()->for($user)->create();
    $bank = Account::factory()->for($user)->create();

    $match = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $cash->id,
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $bank->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index', ['account_id' => $cash->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $match->id));
});

test('the index filters expenses within a date range', function () {
    $user = User::factory()->create();
    ['account' => $account, 'budgetCategory' => $budgetCategory, 'expenseCategory' => $expenseCategory] = expenseFilterFixtures($user);

    $inRange = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'spent_on' => '2026-08-15',
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'spent_on' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $inRange->id));
});

test('the index filters expenses by description search', function () {
    $user = User::factory()->create();
    ['account' => $account, 'budgetCategory' => $budgetCategory, 'expenseCategory' => $expenseCategory] = expenseFilterFixtures($user);

    $match = Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'note' => 'Lunch with client',
    ]);
    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'note' => 'Taxi fare',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index', ['search' => 'lunch']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.id', $match->id));
});

test('the index rejects a date_to before date_from', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.index', ['date_from' => '2026-08-10', 'date_to' => '2026-08-01']))
        ->assertInvalid(['date_to']);
});

test('the index rejects another users account as a filter', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerAccount = Account::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->get(route('expenses.index', ['account_id' => $strangerAccount->id]))
        ->assertInvalid(['account_id']);
});

test('the pdf export downloads a pdf of the filtered expenses', function () {
    $user = User::factory()->create();
    ['account' => $account, 'budgetCategory' => $budgetCategory, 'expenseCategory' => $expenseCategory] = expenseFilterFixtures($user);

    Expense::factory()->for($user)->create([
        'expense_category_id' => $expenseCategory->id,
        'budget_category_id' => $budgetCategory->id,
        'account_id' => $account->id,
        'spent_on' => '2026-08-15',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.export.pdf', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload();
});

test('the pdf export rejects another users category as a filter', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerCategory = ExpenseCategory::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->get(route('expenses.export.pdf', ['expense_category_id' => $strangerCategory->id]))
        ->assertInvalid(['expense_category_id']);
});
