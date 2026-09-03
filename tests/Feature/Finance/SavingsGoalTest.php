<?php

use App\Enums\SavingsGoalStatus;
use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('savings-goals.index'))->assertRedirect(route('login'));
});

test('the index lists the users goals with their live progress', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $goal = SavingsGoal::factory()->for($user)->create(['target_amount' => 200000]);
    SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 85000]);

    $this->actingAs($user)
        ->get(route('savings-goals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('savings/index')
            ->has('savingsGoals', 1)
            ->where('summaries.'.$goal->id.'.saved_amount', '85000.00')
            ->where('summaries.'.$goal->id.'.target_amount', '200000.00')
            ->where('summaries.'.$goal->id.'.usage_percentage', 42.5));
});

test('creating a savings goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('savings-goals.store'), [
            'name' => 'Emergency Fund',
            'target_amount' => '200000',
            'target_date' => '2027-01-01',
            'description' => 'Six months of expenses',
        ])
        ->assertRedirect(route('savings-goals.index'));

    $this->assertDatabaseHas('savings_goals', [
        'user_id' => $user->id,
        'name' => 'Emergency Fund',
        'target_amount' => '200000.00',
        'status' => SavingsGoalStatus::Active->value,
    ]);
});

test('the show page returns the goals progress and transaction history grouped by date', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $goal = SavingsGoal::factory()->for($user)->create(['target_amount' => 50000]);
    SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create([
        'amount' => 20000,
        'transacted_on' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('savings-goals.show', $goal))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('savings/show')
            ->where('summary.saved_amount', '20000.00')
            ->has('transactionGroups', 1));
});

test('updating a savings goal including its status', function () {
    $user = User::factory()->create();
    $goal = SavingsGoal::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('savings-goals.update', $goal), [
            'name' => 'Renamed Goal',
            'target_amount' => '300000',
            'target_date' => null,
            'description' => null,
            'status' => 'completed',
        ])
        ->assertRedirect(route('savings-goals.index'));

    expect($goal->fresh())
        ->name->toBe('Renamed Goal')
        ->status->toBe(SavingsGoalStatus::Completed);
});

test('deleting a savings goal', function () {
    $user = User::factory()->create();
    $goal = SavingsGoal::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('savings-goals.destroy', $goal))
        ->assertRedirect(route('savings-goals.index'));

    $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
});

test('a user cannot view, update, or delete another users savings goal', function () {
    $owner = User::factory()->create();
    $goal = SavingsGoal::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get(route('savings-goals.show', $goal))->assertForbidden();
    $this->actingAs($intruder)->put(route('savings-goals.update', $goal), ['name' => 'x'])->assertForbidden();
    $this->actingAs($intruder)->delete(route('savings-goals.destroy', $goal))->assertForbidden();
});
