<?php

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;

function makeSavingsContext(User $user): array
{
    return [
        'goal' => SavingsGoal::factory()->for($user)->create(['target_amount' => 200000]),
        'account' => Account::factory()->for($user)->create(['balance' => 100000]),
    ];
}

test('guests are redirected to the login page', function () {
    $goal = SavingsGoal::factory()->create();

    $this->get(route('savings-goals.transactions.create', $goal))->assertRedirect(route('login'));
});

test('a contribution debits the account and grows the goal', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);

    $this->actingAs($user)
        ->post(route('savings-goals.transactions.store', $goal), [
            'type' => 'contribution',
            'amount' => '20000',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
            'note' => 'Payday transfer',
        ])
        ->assertRedirect(route('savings-goals.show', $goal));

    $this->assertDatabaseHas('savings_transactions', [
        'user_id' => $user->id,
        'savings_goal_id' => $goal->id,
        'amount' => '20000.00',
        'type' => 'contribution',
    ]);

    expect($account->fresh()->balance)->toBe('80000.00');
});

test('a withdrawal credits the account and shrinks the goal', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 100000]);
    $account->forceFill(['balance' => 0])->save();

    $this->actingAs($user)
        ->post(route('savings-goals.transactions.store', $goal), [
            'type' => 'withdrawal',
            'amount' => '20000',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
        ])
        ->assertRedirect(route('savings-goals.show', $goal));

    expect($account->fresh()->balance)->toBe('20000.00');
});

test('a withdrawal cannot exceed the currently saved amount', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 100000]);

    $this->actingAs($user)
        ->post(route('savings-goals.transactions.store', $goal), [
            'type' => 'withdrawal',
            'amount' => '150000',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);

    $this->assertDatabaseMissing('savings_transactions', ['amount' => '150000.00']);
});

test('editing a transaction reverses the old amount and applies the new one', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    $transaction = SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 20000]);
    $account->forceFill(['balance' => 80000])->save();

    $this->actingAs($user)
        ->put(route('transactions.update', $transaction), [
            'type' => 'contribution',
            'amount' => '30000',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
            'note' => null,
        ])
        ->assertRedirect(route('savings-goals.show', $goal));

    expect($transaction->fresh()->amount)->toBe('30000.00')
        ->and($account->fresh()->balance)->toBe('70000.00');
});

test('increasing an existing withdrawal is validated against the goal without double counting itself', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 100000]);
    $withdrawal = SavingsTransaction::factory()->withdrawal()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 20000]);

    // Current saved balance is 100,000 - 20,000 = 80,000. Increasing this
    // same withdrawal to 90,000 must be checked against the 100,000 balance
    // the goal would have with this withdrawal excluded, not against 80,000.
    $this->actingAs($user)
        ->put(route('transactions.update', $withdrawal), [
            'type' => 'withdrawal',
            'amount' => '90000',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
        ])
        ->assertRedirect(route('savings-goals.show', $goal));

    expect($withdrawal->fresh()->amount)->toBe('90000.00');
});

test('deleting a transaction reverses the account effect', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    $transaction = SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 20000]);
    $account->forceFill(['balance' => 80000])->save();

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $transaction))
        ->assertRedirect(route('savings-goals.show', $goal));

    $this->assertDatabaseMissing('savings_transactions', ['id' => $transaction->id]);
    expect($account->fresh()->balance)->toBe('100000.00');
});

test('deleting a contribution that a later withdrawal depends on is rejected', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);
    $contribution = SavingsTransaction::factory()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 100000]);
    SavingsTransaction::factory()->withdrawal()->for($user)->for($goal, 'savingsGoal')->for($account)->create(['amount' => 80000]);

    $this->actingAs($user)->delete(route('transactions.destroy', $contribution));

    $this->assertDatabaseHas('savings_transactions', ['id' => $contribution->id]);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($user);

    $this->actingAs($user)
        ->post(route('savings-goals.transactions.store', $goal), [
            'type' => 'contribution',
            'amount' => '0',
            'account_id' => $account->id,
            'transacted_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('a user cannot record a transaction against another users goal', function () {
    $owner = User::factory()->create();
    $goal = SavingsGoal::factory()->for($owner)->create();
    $intruder = User::factory()->create();
    $intruderAccount = Account::factory()->for($intruder)->create();

    $this->actingAs($intruder)
        ->post(route('savings-goals.transactions.store', $goal), [
            'type' => 'contribution',
            'amount' => '100',
            'account_id' => $intruderAccount->id,
            'transacted_on' => '2026-08-30',
        ])
        ->assertForbidden();
});

test('a user cannot edit or delete another users savings transaction', function () {
    $owner = User::factory()->create();
    ['goal' => $goal, 'account' => $account] = makeSavingsContext($owner);
    $transaction = SavingsTransaction::factory()->for($owner)->for($goal, 'savingsGoal')->for($account)->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put(route('transactions.update', $transaction), ['amount' => '1'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('transactions.destroy', $transaction))
        ->assertForbidden();
});
