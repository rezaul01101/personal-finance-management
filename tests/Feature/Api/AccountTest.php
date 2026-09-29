<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guests cannot use the accounts api', function () {
    $this->getJson('/api/v1/accounts')->assertUnauthorized();
    $this->postJson('/api/v1/accounts', [])->assertUnauthorized();
});

test('the list contains only my accounts with live balances', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Cash', 'balance' => 1000]);
    Income::factory()->for($user)->create(['account_id' => $account->id, 'amount' => 500]);
    Expense::factory()->for($user)->create(['account_id' => $account->id, 'amount' => 200]);
    Account::factory()->create(['name' => 'Not mine']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/accounts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Cash')
        ->assertJsonPath('data.0.balance', '1300.00')
        ->assertJsonPath('data.0.opening_balance', '1000.00')
        ->assertJsonPath('data.0.status', 'active');
});

test('an account can be created, shown, updated and deleted', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $id = $this->postJson('/api/v1/accounts', ['name' => 'bKash', 'type' => 'mobile_wallet', 'balance' => 250.5])
        ->assertCreated()
        ->assertJsonPath('data.balance', '250.50')
        ->assertJsonPath('data.status', 'active')
        ->json('data.id');

    $this->getJson("/api/v1/accounts/{$id}")->assertOk()->assertJsonPath('data.name', 'bKash');

    $this->putJson("/api/v1/accounts/{$id}", ['name' => 'Nagad', 'type' => 'bank', 'balance' => 10, 'status' => 'archived'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nagad')
        ->assertJsonPath('data.status', 'archived');

    $this->deleteJson("/api/v1/accounts/{$id}")->assertNoContent();
    $this->assertDatabaseMissing('accounts', ['id' => $id]);
});

test('account validation errors return 422', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/accounts', ['name' => '', 'type' => 'nope', 'balance' => 'abc'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type', 'balance']);
});

test('an account with transactions cannot be deleted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    Expense::factory()->for($user)->create(['account_id' => $account->id]);
    Sanctum::actingAs($user);

    $this->deleteJson("/api/v1/accounts/{$account->id}")->assertStatus(409)->assertJsonStructure(['message']);
    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
});

test('another users account is forbidden', function () {
    $account = Account::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/accounts/{$account->id}")->assertForbidden();
    $this->putJson("/api/v1/accounts/{$account->id}", ['name' => 'x', 'type' => 'cash', 'balance' => 1, 'status' => 'active'])->assertForbidden();
    $this->deleteJson("/api/v1/accounts/{$account->id}")->assertForbidden();
    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
});
