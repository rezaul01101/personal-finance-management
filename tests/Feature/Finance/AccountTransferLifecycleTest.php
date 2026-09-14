<?php

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\User;
use App\Services\Finance\AccountCalculator;

test('guests are redirected to the login page', function () {
    $this->get(route('transfers.index'))->assertRedirect(route('login'));
});

test('creating a transfer does not change either account balance, but debits the source and credits the destination in the computed totals', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create(['balance' => 50000]);
    $bkash = Account::factory()->for($user)->create(['balance' => 0]);

    $this->actingAs($user)
        ->post(route('transfers.store'), [
            'amount' => '10000',
            'from_account_id' => $bank->id,
            'to_account_id' => $bkash->id,
            'transferred_on' => '2026-08-30',
            'note' => 'Move to mobile wallet',
        ])
        ->assertRedirect(route('transfers.index'));

    $this->assertDatabaseHas('account_transfers', [
        'user_id' => $user->id,
        'amount' => '10000.00',
        'note' => 'Move to mobile wallet',
    ]);

    $calculator = app(AccountCalculator::class);

    expect($bank->fresh()->balance)->toBe('50000.00')
        ->and($bkash->fresh()->balance)->toBe('0.00')
        ->and($calculator->currentBalance($bank->fresh())->toDecimalString())->toBe('40000.00')
        ->and($calculator->currentBalance($bkash->fresh())->toDecimalString())->toBe('10000.00');
});

test('editing a transfer does not change either account balance, but the computed totals reflect the new amount', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create(['balance' => 40000]);
    $bkash = Account::factory()->for($user)->create(['balance' => 10000]);

    $transfer = AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $bank->id,
        'to_account_id' => $bkash->id,
        'amount' => 10000,
    ]);

    $this->actingAs($user)
        ->put(route('transfers.update', $transfer), [
            'amount' => '15000',
            'from_account_id' => $bank->id,
            'to_account_id' => $bkash->id,
            'transferred_on' => '2026-08-30',
            'note' => null,
        ])
        ->assertRedirect(route('transfers.index'));

    $calculator = app(AccountCalculator::class);

    expect($transfer->fresh()->amount)->toBe('15000.00')
        ->and($bank->fresh()->balance)->toBe('40000.00')
        ->and($bkash->fresh()->balance)->toBe('10000.00')
        ->and($calculator->currentBalance($bank->fresh())->toDecimalString())->toBe('25000.00')
        ->and($calculator->currentBalance($bkash->fresh())->toDecimalString())->toBe('25000.00');
});

test('editing a transfer to move it between different accounts does not change any account balance, but the computed totals move to the new pair', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create(['balance' => 40000]);
    $bkash = Account::factory()->for($user)->create(['balance' => 10000]);
    $cash = Account::factory()->for($user)->create(['balance' => 5000]);

    $transfer = AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $bank->id,
        'to_account_id' => $bkash->id,
        'amount' => 10000,
    ]);

    $this->actingAs($user)->put(route('transfers.update', $transfer), [
        'amount' => '10000',
        'from_account_id' => $bank->id,
        'to_account_id' => $cash->id,
        'transferred_on' => '2026-08-30',
        'note' => null,
    ]);

    $calculator = app(AccountCalculator::class);

    // bkash is no longer party to the transfer at all, so its computed
    // balance returns to its untouched opening figure.
    expect($bank->fresh()->balance)->toBe('40000.00')
        ->and($bkash->fresh()->balance)->toBe('10000.00')
        ->and($cash->fresh()->balance)->toBe('5000.00')
        ->and($calculator->currentBalance($bank->fresh())->toDecimalString())->toBe('30000.00')
        ->and($calculator->currentBalance($bkash->fresh())->toDecimalString())->toBe('10000.00')
        ->and($calculator->currentBalance($cash->fresh())->toDecimalString())->toBe('15000.00');
});

test('deleting a transfer does not change either account balance, and it stops affecting the computed totals', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create(['balance' => 40000]);
    $bkash = Account::factory()->for($user)->create(['balance' => 10000]);

    $transfer = AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $bank->id,
        'to_account_id' => $bkash->id,
        'amount' => 10000,
    ]);

    $this->actingAs($user)
        ->delete(route('transfers.destroy', $transfer))
        ->assertRedirect(route('transfers.index'));

    $this->assertDatabaseMissing('account_transfers', ['id' => $transfer->id]);

    $calculator = app(AccountCalculator::class);

    expect($bank->fresh()->balance)->toBe('40000.00')
        ->and($bkash->fresh()->balance)->toBe('10000.00')
        ->and($calculator->currentBalance($bank->fresh())->toDecimalString())->toBe('40000.00')
        ->and($calculator->currentBalance($bkash->fresh())->toDecimalString())->toBe('10000.00');
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create();
    $bkash = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('transfers.store'), [
            'amount' => '0',
            'from_account_id' => $bank->id,
            'to_account_id' => $bkash->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('the destination account must be different from the source account', function () {
    $user = User::factory()->create();
    $bank = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('transfers.store'), [
            'amount' => '100',
            'from_account_id' => $bank->id,
            'to_account_id' => $bank->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertInvalid(['to_account_id']);
});

test('a user cannot attach another users account to their own transfer', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $bank = Account::factory()->for($user)->create();
    $strangerAccount = Account::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->post(route('transfers.store'), [
            'amount' => '100',
            'from_account_id' => $bank->id,
            'to_account_id' => $strangerAccount->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertInvalid(['to_account_id']);
});

test('a user cannot edit or delete another users transfer', function () {
    $owner = User::factory()->create();
    $bank = Account::factory()->for($owner)->create();
    $bkash = Account::factory()->for($owner)->create();
    $transfer = AccountTransfer::factory()->for($owner)->create([
        'from_account_id' => $bank->id,
        'to_account_id' => $bkash->id,
    ]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('transfers.update', $transfer), ['amount' => '1'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('transfers.destroy', $transfer))
        ->assertForbidden();
});
