<?php

use App\Models\Account;
use App\Models\Income;
use App\Models\User;
use App\Services\Finance\AccountCalculator;

test('guests are redirected to the login page', function () {
    $this->get(route('incomes.index'))->assertRedirect(route('login'));
});

test('creating an income does not change the account balance, but credits the computed current balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);

    $this->actingAs($user)
        ->post(route('incomes.store'), [
            'amount' => '5000',
            'source' => 'salary',
            'account_id' => $account->id,
            'received_on' => '2026-08-30',
            'note' => 'August salary',
        ])
        ->assertRedirect(route('incomes.index'));

    $this->assertDatabaseHas('incomes', [
        'user_id' => $user->id,
        'amount' => '5000.00',
        'source' => 'salary',
        'note' => 'August salary',
    ]);

    expect($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('15000.00');
});

test('editing an income does not change the account balance, but the computed current balance reflects the new amount', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $income = Income::factory()->for($user)->create([
        'account_id' => $account->id,
        'source' => 'freelance',
        'amount' => 2000,
    ]);

    $this->actingAs($user)
        ->put(route('incomes.update', $income), [
            'amount' => '3000',
            'source' => 'freelance',
            'account_id' => $account->id,
            'received_on' => '2026-08-30',
            'note' => null,
        ])
        ->assertRedirect(route('incomes.index'));

    expect($income->fresh()->amount)->toBe('3000.00')
        ->and($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('13000.00');
});

test('editing an income to move it to a different account does not change either account balance, but credits the new account and clears the old one in the computed totals', function () {
    $user = User::factory()->create();
    $oldAccount = Account::factory()->for($user)->create(['balance' => 10000]);
    $newAccount = Account::factory()->for($user)->create(['balance' => 5000]);

    $income = Income::factory()->for($user)->create([
        'account_id' => $oldAccount->id,
        'source' => 'bonus',
        'amount' => 1000,
    ]);

    $this->actingAs($user)->put(route('incomes.update', $income), [
        'amount' => '1000',
        'source' => 'bonus',
        'account_id' => $newAccount->id,
        'received_on' => '2026-08-30',
        'note' => null,
    ]);

    $calculator = app(AccountCalculator::class);

    expect($oldAccount->fresh()->balance)->toBe('10000.00')
        ->and($newAccount->fresh()->balance)->toBe('5000.00')
        ->and($calculator->currentBalance($oldAccount->fresh())->toDecimalString())->toBe('10000.00')
        ->and($calculator->currentBalance($newAccount->fresh())->toDecimalString())->toBe('6000.00');
});

test('deleting an income does not change the account balance, and it stops affecting the computed current balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $income = Income::factory()->for($user)->create([
        'account_id' => $account->id,
        'source' => 'business',
        'amount' => 1000,
    ]);

    $this->actingAs($user)
        ->delete(route('incomes.destroy', $income))
        ->assertRedirect(route('incomes.index'));

    $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
    expect($account->fresh()->balance)->toBe('10000.00')
        ->and(app(AccountCalculator::class)->currentBalance($account->fresh())->toDecimalString())->toBe('10000.00');
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), [
            'amount' => '0',
            'source' => 'salary',
            'account_id' => $account->id,
            'received_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('source must be a valid enum value', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), [
            'amount' => '100',
            'source' => 'lottery',
            'account_id' => $account->id,
            'received_on' => '2026-08-30',
        ])
        ->assertInvalid(['source']);
});

test('a user cannot attach another users account to their own income', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerAccount = Account::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->post(route('incomes.store'), [
            'amount' => '100',
            'source' => 'other',
            'account_id' => $strangerAccount->id,
            'received_on' => '2026-08-30',
        ])
        ->assertInvalid(['account_id']);
});

test('a user cannot edit or delete another users income', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $income = Income::factory()->for($owner)->create(['account_id' => $account->id]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('incomes.update', $income), ['amount' => '1'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('incomes.destroy', $income))
        ->assertForbidden();
});
