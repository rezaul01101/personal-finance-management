<?php

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot reach savings goal endpoints', function () {
    $this->getJson('/api/v1/savings-goals')->assertUnauthorized();
    $this->postJson('/api/v1/savings-goals', [])->assertUnauthorized();
    $this->getJson('/api/v1/savings-goals/1')->assertUnauthorized();
    $this->postJson('/api/v1/savings-goals/1/transactions', [])->assertUnauthorized();
    $this->getJson('/api/v1/savings-transactions/1')->assertUnauthorized();
});

test('savings goals list only the owners goals with live progress', function () {
    $user = User::factory()->create();
    $goal = SavingsGoal::factory()->for($user)->create(['target_amount' => 1000]);
    $account = Account::factory()->for($user)->create();
    SavingsTransaction::factory()->for($user)->create([
        'savings_goal_id' => $goal->id, 'account_id' => $account->id, 'amount' => 250,
    ]);
    SavingsGoal::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/savings-goals')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $goal->id)
        ->assertJsonPath('data.0.target_amount', '1000.00')
        ->assertJsonPath('data.0.saved_amount', '250.00')
        ->assertJsonPath('data.0.remaining_amount', '750.00')
        ->assertJsonPath('data.0.usage_percentage', 25);
});

test('a savings goal can be created, shown, updated and deleted', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $id = $this->postJson('/api/v1/savings-goals', [
        'name' => 'Laptop', 'target_amount' => 50000, 'target_date' => '2027-01-31', 'description' => 'New one',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Laptop')
        ->assertJsonPath('data.target_amount', '50000.00')
        ->assertJsonPath('data.target_date', '2027-01-31')
        ->assertJsonPath('data.status', 'active')
        ->json('data.id');

    $this->getJson("/api/v1/savings-goals/{$id}")
        ->assertOk()
        ->assertJsonPath('data.saved_amount', '0.00')
        ->assertJsonPath('data.transactions', []);

    $this->putJson("/api/v1/savings-goals/{$id}", [
        'name' => 'Laptop Pro', 'target_amount' => 60000, 'status' => 'completed',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Laptop Pro')
        ->assertJsonPath('data.status', 'completed');

    $this->deleteJson("/api/v1/savings-goals/{$id}")->assertNoContent();
    $this->assertDatabaseMissing('savings_goals', ['id' => $id]);
});

test('savings goal validation errors are returned as 422', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/savings-goals', ['target_amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'target_amount']);

});

test('another users goal is forbidden', function () {
    $goal = SavingsGoal::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/savings-goals/{$goal->id}")->assertForbidden();
    $this->putJson("/api/v1/savings-goals/{$goal->id}", [
        'name' => 'x', 'target_amount' => 5, 'status' => 'active',
    ])->assertForbidden();
    $this->deleteJson("/api/v1/savings-goals/{$goal->id}")->assertForbidden();
    $this->assertModelExists($goal);
});
