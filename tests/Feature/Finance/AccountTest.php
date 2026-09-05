<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

test('a user can view their own accounts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('accounts/index')
            ->has('accounts', 1)
            ->where('accounts.0.id', $account->id));
});

test('a user can create an account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), [
            'name' => 'Cash Wallet',
            'type' => AccountType::Cash->value,
        ])
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Cash Wallet',
        'type' => AccountType::Cash->value,
        'balance' => '0.00',
    ]);
});

test('creating an account requires a name and a valid type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), ['name' => '', 'type' => 'invalid'])
        ->assertInvalid(['name', 'type']);
});

test('a user can update their own account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['name' => 'Old Name', 'balance' => 1000]);

    $this->actingAs($user)
        ->put(route('accounts.update', $account), [
            'name' => 'New Name',
            'type' => AccountType::Bank->value,
            'balance' => 1000,
            'status' => 'active',
        ])
        ->assertRedirect(route('accounts.index'));

    expect($account->fresh()->name)->toBe('New Name');
});

test('a user cannot update another users account', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('accounts.update', $account), [
            'name' => 'Hijacked',
            'type' => AccountType::Bank->value,
            'status' => 'active',
        ])
        ->assertForbidden();

    expect($account->fresh()->name)->not->toBe('Hijacked');
});

test('a user can delete their own account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});

test('a user cannot delete another users account', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->delete(route('accounts.destroy', $account))
        ->assertForbidden();

    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
});

test('a user can set an opening balance when creating an account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Cash',
        'type' => AccountType::Cash->value,
        'balance' => 15000,
    ]);

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Cash',
        'balance' => '15000.00',
    ]);
});

test('omitting the balance when creating an account defaults it to zero', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Cash',
        'type' => AccountType::Cash->value,
    ]);

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Cash',
        'balance' => '0.00',
    ]);
});

test('a user can edit an existing account\'s balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 1000]);

    $this->actingAs($user)
        ->put(route('accounts.update', $account), [
            'name' => $account->name,
            'type' => $account->type->value,
            'balance' => 7500,
            'status' => 'active',
        ])
        ->assertRedirect(route('accounts.index'));

    expect($account->fresh()->balance)->toBe('7500.00');
});
