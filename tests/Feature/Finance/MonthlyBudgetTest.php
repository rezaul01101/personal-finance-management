<?php

use App\Models\BudgetCategory;
use App\Models\MonthlyBudget;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('budgets.index'))->assertRedirect(route('login'));
});

test('the index shows the users active budget categories and any saved amounts for the period', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create(['name' => 'Family']);
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    $this->actingAs($user)
        ->get(route('budgets.index', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('budgets/index')
            ->where('year', 2026)
            ->where('month', 8)
            ->has('budgetCategories', 1)
            ->where('amounts.'.$family->id, '15000.00'));
});

test('the index falls back to the previous months amount when the current month has no budget set', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create(['name' => 'Family']);
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 7,
        'amount' => 12000,
    ]);

    $this->actingAs($user)
        ->get(route('budgets.index', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('budgets/index')
            ->where('amounts.'.$family->id, '12000.00')
            ->where('suggestedCategoryIds', [$family->id]));

    $this->assertDatabaseMissing('monthly_budgets', [
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
    ]);
});

test('the index uses the most recent prior month with a saved budget, not just the immediately preceding one', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 5,
        'amount' => 9000,
    ]);
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 6,
        'amount' => 11000,
    ]);

    $this->actingAs($user)
        ->get(route('budgets.index', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('amounts.'.$family->id, '11000.00'));
});

test('the index leaves the amount unset when no current or prior budget exists for the category', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('budgets.index', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('amounts.'.$family->id));
});

test('the index does not mark a category as suggested when it already has a budget for the current month', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 15000,
    ]);

    $this->actingAs($user)
        ->get(route('budgets.index', ['year' => 2026, 'month' => 8]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('suggestedCategoryIds', []));
});

test('saving a budget creates a monthly budget row for each category with an amount', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('budgets.store'), [
            'year' => 2026,
            'month' => 8,
            'budgets' => [
                ['budget_category_id' => $family->id, 'amount' => '15000'],
            ],
        ])
        ->assertRedirect(route('budgets.index', ['year' => 2026, 'month' => 8]));

    $this->assertDatabaseHas('monthly_budgets', [
        'user_id' => $user->id,
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => '15000.00',
    ]);
});

test('saving with an amount for an existing period updates it rather than duplicating it', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 12000,
    ]);

    $this->actingAs($user)->post(route('budgets.store'), [
        'year' => 2026,
        'month' => 8,
        'budgets' => [
            ['budget_category_id' => $family->id, 'amount' => '18000'],
        ],
    ]);

    expect(MonthlyBudget::query()->where('budget_category_id', $family->id)->count())->toBe(1);
    $this->assertDatabaseHas('monthly_budgets', [
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => '18000.00',
    ]);
});

test('clearing a category amount removes its budget for that month', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();
    MonthlyBudget::factory()->for($user)->create([
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
        'amount' => 12000,
    ]);

    $this->actingAs($user)->post(route('budgets.store'), [
        'year' => 2026,
        'month' => 8,
        'budgets' => [
            ['budget_category_id' => $family->id, 'amount' => null],
        ],
    ]);

    $this->assertDatabaseMissing('monthly_budgets', [
        'budget_category_id' => $family->id,
        'year' => 2026,
        'month' => 8,
    ]);
});

test('the same category can have a different budget in a different month', function () {
    $user = User::factory()->create();
    $family = BudgetCategory::factory()->for($user)->create();

    $this->actingAs($user)->post(route('budgets.store'), [
        'year' => 2026,
        'month' => 7,
        'budgets' => [['budget_category_id' => $family->id, 'amount' => '12000']],
    ]);

    $this->actingAs($user)->post(route('budgets.store'), [
        'year' => 2026,
        'month' => 8,
        'budgets' => [['budget_category_id' => $family->id, 'amount' => '15000']],
    ]);

    $this->assertDatabaseHas('monthly_budgets', ['budget_category_id' => $family->id, 'month' => 7, 'amount' => '12000.00']);
    $this->assertDatabaseHas('monthly_budgets', ['budget_category_id' => $family->id, 'month' => 8, 'amount' => '15000.00']);
});

test('a user cannot set a budget for another users budget category', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = BudgetCategory::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->post(route('budgets.store'), [
            'year' => 2026,
            'month' => 8,
            'budgets' => [['budget_category_id' => $category->id, 'amount' => '5000']],
        ])
        ->assertInvalid(['budgets.0.budget_category_id']);
});
