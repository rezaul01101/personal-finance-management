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

    $this->get(route('loans.repayments.create', $loan))->assertRedirect(route('login'));
});

test('a repayment on a loan given does not touch any account and grows the held balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 5000]);
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => 10000]);

    $this->actingAs($user)
        ->post(route('loans.repayments.store', $loan), [
            'amount' => '4000',
            'repaid_on' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.show', $loan));

    $this->assertDatabaseHas('loan_repayments', [
        'loan_id' => $loan->id,
        'account_id' => null,
        'amount' => '4000.00',
    ]);

    expect($account->fresh()->balance)->toBe('5000.00');
});

test('a repayment on a loan given still credits nothing even if an account_id is submitted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 5000]);
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => 10000]);

    $this->actingAs($user)->post(route('loans.repayments.store', $loan), [
        'amount' => '4000',
        'account_id' => $account->id,
        'repaid_on' => '2026-08-30',
    ]);

    $this->assertDatabaseHas('loan_repayments', ['loan_id' => $loan->id, 'account_id' => null]);
    expect($account->fresh()->balance)->toBe('5000.00');
});

test('a repayment on a loan taken immediately debits the chosen account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 5000]);
    $loan = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => 20000]);

    $this->actingAs($user)
        ->post(route('loans.repayments.store', $loan), [
            'amount' => '5000',
            'account_id' => $account->id,
            'repaid_on' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.show', $loan));

    expect($account->fresh()->balance)->toBe('0.00');
});

test('account_id is required when repaying a loan taken', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => 20000]);

    $this->actingAs($user)
        ->post(route('loans.repayments.store', $loan), [
            'amount' => '5000',
            'repaid_on' => '2026-08-30',
        ])
        ->assertInvalid(['account_id']);
});

test('a repayment cannot exceed the loan outstanding balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => 10000]);

    $this->actingAs($user)
        ->post(route('loans.repayments.store', $loan), [
            'amount' => '10001',
            'repaid_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('editing a repayment on a loan taken reverses the old account effect and reapplies the new one', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $loan = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => 20000]);
    $repayment = LoanRepayment::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $account->id,
        'amount' => 2000,
    ]);
    // Simulate the debit that would have happened when the repayment was created.
    $account->forceFill(['balance' => 8000])->save();

    $this->actingAs($user)
        ->put(route('repayments.update', $repayment), [
            'amount' => '3000',
            'account_id' => $account->id,
            'repaid_on' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.show', $loan));

    expect($repayment->fresh()->amount)->toBe('3000.00')
        ->and($account->fresh()->balance)->toBe('7000.00');
});

test('editing a repayment above the outstanding balance excluding itself is rejected', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => 10000]);
    $repayment = LoanRepayment::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => null,
        'amount' => 4000,
    ]);

    $this->actingAs($user)
        ->put(route('repayments.update', $repayment), [
            'amount' => '10001',
            'repaid_on' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('deleting a repayment on a loan taken credits the account back', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $loan = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => 20000]);
    $repayment = LoanRepayment::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $account->id,
        'amount' => 1000,
    ]);
    $account->forceFill(['balance' => 9000])->save();

    $this->actingAs($user)
        ->delete(route('repayments.destroy', $repayment))
        ->assertRedirect(route('loans.show', $loan));

    $this->assertDatabaseMissing('loan_repayments', ['id' => $repayment->id]);
    expect($account->fresh()->balance)->toBe('10000.00');
});

test('deleting a repayment on a loan given is rejected if a transfer already relies on it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => 10000]);
    $repayment = LoanRepayment::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => null,
        'amount' => 4000,
    ]);
    LoanTransfer::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $account->id,
        'amount' => 4000,
    ]);

    $this->actingAs($user)
        ->delete(route('repayments.destroy', $repayment))
        ->assertSessionHasErrors('amount');

    $this->assertDatabaseHas('loan_repayments', ['id' => $repayment->id]);
});

test('a user cannot view, edit, or delete another users repayment', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $loan = Loan::factory()->for($owner)->given()->create(['account_id' => $account->id]);
    $repayment = LoanRepayment::factory()->for($owner)->create(['loan_id' => $loan->id]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('repayments.edit', $repayment))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('repayments.update', $repayment), ['amount' => '1', 'repaid_on' => '2026-08-30'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('repayments.destroy', $repayment))
        ->assertForbidden();
});
