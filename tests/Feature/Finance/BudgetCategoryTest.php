<?php

use App\Enums\CategoryStatus;
use App\Models\BudgetCategory;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('budget-categories.index'))->assertRedirect(route('login'));
});

test('a user can view their own budget categories', function () {
    $user = User::factory()->create();
    $category = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('budget-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('budget-categories/index')
            ->has('budgetCategories', 1)
            ->where('budgetCategories.0.id', $category->id));
});

test('a user can create a budget category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('budget-categories.store'), ['name' => 'Family'])
        ->assertRedirect(route('budget-categories.index'));

    $this->assertDatabaseHas('budget_categories', [
        'user_id' => $user->id,
        'name' => 'Family',
    ]);
});

test('budget category names must be unique per user', function () {
    $user = User::factory()->create();
    BudgetCategory::factory()->for($user)->create(['name' => 'Family']);

    $this->actingAs($user)
        ->post(route('budget-categories.store'), ['name' => 'Family'])
        ->assertInvalid(['name']);
});

test('two different users can each have a budget category with the same name', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    BudgetCategory::factory()->for($userA)->create(['name' => 'Family']);

    $this->actingAs($userB)
        ->post(route('budget-categories.store'), ['name' => 'Family'])
        ->assertRedirect(route('budget-categories.index'));

    $this->assertDatabaseHas('budget_categories', ['user_id' => $userB->id, 'name' => 'Family']);
});

test('a user can archive their own budget category', function () {
    $user = User::factory()->create();
    $category = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('budget-categories.update', $category), [
            'name' => $category->name,
            'status' => CategoryStatus::Archived->value,
        ])
        ->assertRedirect(route('budget-categories.index'));

    expect($category->fresh()->status)->toBe(CategoryStatus::Archived);
});

test('a user cannot update another users budget category', function () {
    $owner = User::factory()->create();
    $category = BudgetCategory::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('budget-categories.update', $category), [
            'name' => 'Hijacked',
            'status' => CategoryStatus::Active->value,
        ])
        ->assertForbidden();
});

test('a user can delete their own budget category', function () {
    $user = User::factory()->create();
    $category = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('budget-categories.destroy', $category))
        ->assertRedirect(route('budget-categories.index'));

    $this->assertDatabaseMissing('budget_categories', ['id' => $category->id]);
});

test('a user cannot delete another users budget category', function () {
    $owner = User::factory()->create();
    $category = BudgetCategory::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('budget-categories.destroy', $category))
        ->assertForbidden();
});
