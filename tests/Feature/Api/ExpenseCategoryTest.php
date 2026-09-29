<?php

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot use the expense-categories api', function () {
    $this->getJson('/api/v1/expense-categories')->assertUnauthorized();
    $this->postJson('/api/v1/expense-categories', [])->assertUnauthorized();
});

test('the list contains all my rows including archived and none of anyone elses', function () {
    $user = User::factory()->create();
    ExpenseCategory::factory()->for($user)->create(['name' => 'Food']);
    ExpenseCategory::factory()->for($user)->archived()->create(['name' => 'Travel']);
    ExpenseCategory::factory()->create(['name' => 'Other user']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/expense-categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Food')
        ->assertJsonPath('data.1.status', 'archived');
});

test('a category can be created, shown, updated and deleted', function () {
    Sanctum::actingAs(User::factory()->create());

    $id = $this->postJson('/api/v1/expense-categories', ['name' => 'Groceries', 'icon' => 'x'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'active')
        ->json('data.id');

    $this->getJson("/api/v1/expense-categories/{$id}")->assertOk()->assertJsonPath('data.name', 'Groceries');

    $this->putJson("/api/v1/expense-categories/{$id}", ['name' => 'Food', 'icon' => null, 'status' => 'archived'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Food')
        ->assertJsonPath('data.status', 'archived');

    $this->deleteJson("/api/v1/expense-categories/{$id}")->assertNoContent();
});

test('validation rejects blank and duplicate names', function () {
    $user = User::factory()->create();
    ExpenseCategory::factory()->for($user)->create(['name' => 'Food']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/expense-categories', ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson('/api/v1/expense-categories', ['name' => 'Food'])->assertUnprocessable()->assertJsonValidationErrors('name');
});

test('a category with expenses cannot be deleted', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->for($user)->create();
    Expense::factory()->for($user)->create([
        'expense_category_id' => $user->expenseCategories()->firstOr(fn () => ExpenseCategory::factory()->for($user)->create())->id,
        'budget_category_id' => $user->budgetCategories()->firstOr(fn () => BudgetCategory::factory()->for($user)->create())->id,
        'account_id' => Account::factory()->for($user)->create()->id,
    ]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/expense-categories/{$category->id}")->assertStatus(409);
    $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
});

test('another users category is forbidden', function () {
    $category = ExpenseCategory::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/expense-categories/{$category->id}")->assertForbidden();
    $this->putJson("/api/v1/expense-categories/{$category->id}", ['name' => 'x', 'status' => 'active'])->assertForbidden();
    $this->deleteJson("/api/v1/expense-categories/{$category->id}")->assertForbidden();
});
