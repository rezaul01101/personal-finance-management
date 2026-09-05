<?php

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanTransfer;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id]);

    $this->get(route('loans.transfers.create', $loan))->assertRedirect(route('login'));
});

test('a transfer credits the destination account and reduces the held balance', function () {
    $user = User::factory()->create();
    $sourceAccount = Account::factory()->for($user)->create();
    $destinationAccount = Account::factory()->for($user)->create(['balance' => 1000]);
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $sourceAccount->id, 'amount' => 10000]);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loan->id, 'account_id' => null, 'amount' => 5000]);

    $this->actingAs($user)
        ->post(route('loans.transfers.store', $loan), [
            'amount' => '2000',
            'account_id' => $destinationAccount->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.show', $loan));

    $this->assertDatabaseHas('loan_transfers', [
        'loan_id' => $loan->id,
        'account_id' => $destinationAccount->id,
        'amount' => '2000.00',
    ]);

    expect($destinationAccount->fresh()->balance)->toBe('3000.00');
});

test('a transfer cannot exceed the held (untransferred) balance', function () {
    $user = User::factory()->create();
    $sourceAccount = Account::factory()->for($user)->create();
    $destinationAccount = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $sourceAccount->id, 'amount' => 10000]);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loan->id, 'account_id' => null, 'amount' => 3000]);

    $this->actingAs($user)
        ->post(route('loans.transfers.store', $loan), [
            'amount' => '3001',
            'account_id' => $destinationAccount->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('a transfer cannot be created for a loan taken', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $destinationAccount = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('loans.transfers.create', $loan))
        ->assertNotFound();

    $this->actingAs($user)
        ->post(route('loans.transfers.store', $loan), [
            'amount' => '100',
            'account_id' => $destinationAccount->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertNotFound();
});

test('editing a transfer reverses the old account effect and reapplies the new one', function () {
    $user = User::factory()->create();
    $sourceAccount = Account::factory()->for($user)->create();
    $destinationAccount = Account::factory()->for($user)->create(['balance' => 1000]);
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $sourceAccount->id, 'amount' => 10000]);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loan->id, 'account_id' => null, 'amount' => 5000]);
    $transfer = LoanTransfer::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $destinationAccount->id,
        'amount' => 2000,
    ]);
    // Simulate the credit that would have happened when the transfer was created.
    $destinationAccount->forceFill(['balance' => 3000])->save();

    $this->actingAs($user)
        ->put(route('loans.transfers.update', [$loan, $transfer]), [
            'amount' => '3000',
            'account_id' => $destinationAccount->id,
            'transferred_on' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.show', $loan));

    expect($transfer->fresh()->amount)->toBe('3000.00')
        ->and($destinationAccount->fresh()->balance)->toBe('4000.00');
});

test('a mismatched transfer/loan pair 404s even when both are owned by the requester', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loanA = Loan::factory()->for($user)->given()->create(['account_id' => $account->id]);
    $loanB = Loan::factory()->for($user)->given()->create(['account_id' => $account->id]);
    $transfer = LoanTransfer::factory()->for($user)->create(['loan_id' => $loanA->id, 'account_id' => $account->id]);

    $this->actingAs($user)
        ->get(route('loans.transfers.edit', [$loanB, $transfer]))
        ->assertNotFound();
});

test('deleting a transfer reverses its account effect', function () {
    $user = User::factory()->create();
    $sourceAccount = Account::factory()->for($user)->create();
    $destinationAccount = Account::factory()->for($user)->create(['balance' => 1000]);
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $sourceAccount->id, 'amount' => 10000]);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loan->id, 'account_id' => null, 'amount' => 5000]);
    $transfer = LoanTransfer::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $destinationAccount->id,
        'amount' => 2000,
    ]);
    $destinationAccount->forceFill(['balance' => 3000])->save();

    $this->actingAs($user)
        ->delete(route('loans.transfers.destroy', [$loan, $transfer]))
        ->assertRedirect(route('loans.show', $loan));

    $this->assertDatabaseMissing('loan_transfers', ['id' => $transfer->id]);
    expect($destinationAccount->fresh()->balance)->toBe('1000.00');
});

test('a user cannot view, edit, or delete another users transfer', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $loan = Loan::factory()->for($owner)->given()->create(['account_id' => $account->id]);
    $transfer = LoanTransfer::factory()->for($owner)->create(['loan_id' => $loan->id, 'account_id' => $account->id]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('loans.transfers.edit', [$loan, $transfer]))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('loans.transfers.update', [$loan, $transfer]), ['amount' => '1', 'account_id' => $account->id, 'transferred_on' => '2026-08-30'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('loans.transfers.destroy', [$loan, $transfer]))
        ->assertForbidden();
});
