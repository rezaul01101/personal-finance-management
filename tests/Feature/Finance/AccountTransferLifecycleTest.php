<?php

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('transfers.index'))->assertRedirect(route('login'));
});

test('creating a transfer debits the source account and credits the destination account', function () {
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

    expect($bank->fresh()->balance)->toBe('40000.00')
        ->and($bkash->fresh()->balance)->toBe('10000.00');
});

test('editing a transfer reverses the old amount and applies the new one', function () {
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

    expect($transfer->fresh()->amount)->toBe('15000.00')
        ->and($bank->fresh()->balance)->toBe('35000.00')
        ->and($bkash->fresh()->balance)->toBe('15000.00');
});

test('editing a transfer to move it between different accounts reverses the old pair and applies the new one', function () {
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

    // "from" account is unchanged and the amount is unchanged, so its net
    // effect is a reversal followed by an identical re-application - no change.
    expect($bank->fresh()->balance)->toBe('40000.00')
        ->and($bkash->fresh()->balance)->toBe('0.00')
        ->and($cash->fresh()->balance)->toBe('15000.00');
});

test('deleting a transfer reverses both account balances', function () {
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
    expect($bank->fresh()->balance)->toBe('50000.00')
        ->and($bkash->fresh()->balance)->toBe('0.00');
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
