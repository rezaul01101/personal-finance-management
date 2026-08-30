<?php

use App\Enums\CategoryStatus;
use App\Models\ExpenseCategory;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('expense-categories.index'))->assertRedirect(route('login'));
});

test('a user can view their own expense categories', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('expense-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('expense-categories/index')
            ->has('expenseCategories', 1)
            ->where('expenseCategories.0.id', $category->id));
});

test('a user can create an expense category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('expense-categories.store'), ['name' => 'Food'])
        ->assertRedirect(route('expense-categories.index'));

    $this->assertDatabaseHas('expense_categories', [
        'user_id' => $user->id,
        'name' => 'Food',
    ]);
});

test('expense category names must be unique per user', function () {
    $user = User::factory()->create();
    ExpenseCategory::factory()->for($user)->create(['name' => 'Food']);

    $this->actingAs($user)
        ->post(route('expense-categories.store'), ['name' => 'Food'])
        ->assertInvalid(['name']);
});

test('a user can archive their own expense category', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('expense-categories.update', $category), [
            'name' => $category->name,
            'status' => CategoryStatus::Archived->value,
        ])
        ->assertRedirect(route('expense-categories.index'));

    expect($category->fresh()->status)->toBe(CategoryStatus::Archived);
});

test('a user cannot update another users expense category', function () {
    $owner = User::factory()->create();
    $category = ExpenseCategory::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('expense-categories.update', $category), [
            'name' => 'Hijacked',
            'status' => CategoryStatus::Active->value,
        ])
        ->assertForbidden();
});

test('a user can delete their own expense category', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('expense-categories.destroy', $category))
        ->assertRedirect(route('expense-categories.index'));

    $this->assertDatabaseMissing('expense_categories', ['id' => $category->id]);
});

test('a user cannot delete another users expense category', function () {
    $owner = User::factory()->create();
    $category = ExpenseCategory::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('expense-categories.destroy', $category))
        ->assertForbidden();
});
