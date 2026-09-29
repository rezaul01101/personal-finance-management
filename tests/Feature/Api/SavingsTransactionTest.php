<?php

use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function savedTransaction(User $user, SavingsGoal $goal, Account $account, float $amount, string $type = 'contribution'): SavingsTransaction
{
    return SavingsTransaction::factory()->for($user)->create([
        'savings_goal_id' => $goal->id, 'account_id' => $account->id, 'amount' => $amount, 'type' => $type,
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->goal = SavingsGoal::factory()->for($this->user)->create();
    $this->account = Account::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);
});

test('a contribution can be recorded and appears on the goal', function () {
    $this->postJson("/api/v1/savings-goals/{$this->goal->id}/transactions", [
        'type' => 'contribution', 'amount' => 300, 'account_id' => $this->account->id,
        'transacted_on' => '2026-08-10', 'note' => 'Bonus',
    ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'contribution')
        ->assertJsonPath('data.amount', '300.00')
        ->assertJsonPath('data.account.id', $this->account->id)
        ->assertJsonPath('data.transacted_on', '2026-08-10');

    $this->getJson("/api/v1/savings-goals/{$this->goal->id}")
        ->assertJsonPath('data.saved_amount', '300.00')
        ->assertJsonCount(1, 'data.transactions');
});

test('a withdrawal above the saved amount is rejected on the amount field', function () {
    savedTransaction($this->user, $this->goal, $this->account, 100);

    $this->postJson("/api/v1/savings-goals/{$this->goal->id}/transactions", [
        'type' => 'withdrawal', 'amount' => 150, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-10',
    ])->assertUnprocessable()->assertJsonValidationErrors('amount');

    $this->postJson("/api/v1/savings-goals/{$this->goal->id}/transactions", [
        'type' => 'withdrawal', 'amount' => 40, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-10',
    ])->assertCreated();
});

test('transaction validation errors are returned', function () {
    $this->postJson("/api/v1/savings-goals/{$this->goal->id}/transactions", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'amount', 'account_id', 'transacted_on']);

    $foreignAccount = Account::factory()->create();
    $this->postJson("/api/v1/savings-goals/{$this->goal->id}/transactions", [
        'type' => 'contribution', 'amount' => 5, 'account_id' => $foreignAccount->id, 'transacted_on' => '2026-08-10',
    ])->assertUnprocessable()->assertJsonValidationErrors('account_id');
});

test('a transaction can be shown, updated and deleted', function () {
    $transaction = savedTransaction($this->user, $this->goal, $this->account, 100);

    $this->getJson("/api/v1/savings-transactions/{$transaction->id}")
        ->assertOk()
        ->assertJsonPath('data.amount', '100.00');

    $this->putJson("/api/v1/savings-transactions/{$transaction->id}", [
        'type' => 'contribution', 'amount' => 120, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-11',
    ])->assertOk()->assertJsonPath('data.amount', '120.00');

    $this->deleteJson("/api/v1/savings-transactions/{$transaction->id}")->assertNoContent();
    $this->assertModelMissing($transaction);
});

test('deleting or shrinking a contribution that a withdrawal depends on is rejected', function () {
    $contribution = savedTransaction($this->user, $this->goal, $this->account, 100);
    savedTransaction($this->user, $this->goal, $this->account, 80, 'withdrawal');

    $this->deleteJson("/api/v1/savings-transactions/{$contribution->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
    $this->assertModelExists($contribution);

    $this->putJson("/api/v1/savings-transactions/{$contribution->id}", [
        'type' => 'contribution', 'amount' => 50, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-11',
    ])->assertUnprocessable()->assertJsonValidationErrors('amount');
    expect($contribution->fresh()->amount)->toBe('100.00');
});

test('another users goal and transactions are forbidden', function () {
    $other = User::factory()->create();
    $goal = SavingsGoal::factory()->for($other)->create();
    $account = Account::factory()->for($other)->create();
    $transaction = savedTransaction($other, $goal, $account, 100);

    $this->postJson("/api/v1/savings-goals/{$goal->id}/transactions", [
        'type' => 'contribution', 'amount' => 5, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-10',
    ])->assertForbidden();
    $this->getJson("/api/v1/savings-transactions/{$transaction->id}")->assertForbidden();
    $this->putJson("/api/v1/savings-transactions/{$transaction->id}", [
        'type' => 'contribution', 'amount' => 5, 'account_id' => $this->account->id, 'transacted_on' => '2026-08-10',
    ])->assertForbidden();
    $this->deleteJson("/api/v1/savings-transactions/{$transaction->id}")->assertForbidden();
    $this->assertModelExists($transaction);
});
