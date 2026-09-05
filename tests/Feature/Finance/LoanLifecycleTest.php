<?php

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Services\Finance\LoanService;
use Illuminate\Database\QueryException;

test('guests are redirected to the login page', function () {
    $this->get(route('loans.index'))->assertRedirect(route('login'));
});

test('creating a loan given debits the source account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'given',
            'person_name' => 'Anamul',
            'amount' => '5000',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
            'note' => 'Lent for emergency',
        ])
        ->assertRedirect(route('loans.index', ['direction' => 'given']));

    $this->assertDatabaseHas('loans', [
        'user_id' => $user->id,
        'type' => 'given',
        'person_name' => 'Anamul',
        'amount' => '5000.00',
    ]);

    expect($account->fresh()->balance)->toBe('5000.00');
});

test('creating a loan taken credits the destination account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'taken',
            'person_name' => 'Rahim',
            'amount' => '20000',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.index', ['direction' => 'taken']));

    expect($account->fresh()->balance)->toBe('30000.00');
});

test('editing a loan given reverses the old amount and applies the new one', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $loan = Loan::factory()->for($user)->given()->create([
        'account_id' => $account->id,
        'amount' => 2000,
    ]);
    // Simulate the debit that would have happened when the loan was created.
    $account->forceFill(['balance' => 8000])->save();

    $this->actingAs($user)
        ->put(route('loans.update', $loan), [
            'person_name' => $loan->person_name,
            'amount' => '3000',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertRedirect(route('loans.index', ['direction' => 'given']));

    expect($loan->fresh()->amount)->toBe('3000.00')
        ->and($account->fresh()->balance)->toBe('7000.00');
});

test('editing a loan to move it to a different account reverses the old account and applies the new one', function () {
    $user = User::factory()->create();
    $oldAccount = Account::factory()->for($user)->create(['balance' => 10000]);
    $newAccount = Account::factory()->for($user)->create(['balance' => 5000]);
    $loan = Loan::factory()->for($user)->taken()->create([
        'account_id' => $oldAccount->id,
        'amount' => 1000,
    ]);
    $oldAccount->forceFill(['balance' => 11000])->save();

    $this->actingAs($user)->put(route('loans.update', $loan), [
        'person_name' => $loan->person_name,
        'amount' => '1000',
        'account_id' => $newAccount->id,
        'loan_date' => '2026-08-30',
    ]);

    expect($oldAccount->fresh()->balance)->toBe('10000.00')
        ->and($newAccount->fresh()->balance)->toBe('6000.00');
});

test('the loan amount cannot be edited below what has already been repaid', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->taken()->create([
        'account_id' => $account->id,
        'amount' => 10000,
    ]);
    LoanRepayment::factory()->for($user)->create([
        'loan_id' => $loan->id,
        'account_id' => $account->id,
        'amount' => 4000,
    ]);

    $this->actingAs($user)
        ->put(route('loans.update', $loan), [
            'person_name' => $loan->person_name,
            'amount' => '3000',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('deleting a loan reverses the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => 10000]);
    $loan = Loan::factory()->for($user)->given()->create([
        'account_id' => $account->id,
        'amount' => 1000,
    ]);
    $account->forceFill(['balance' => 9000])->save();

    $this->actingAs($user)
        ->delete(route('loans.destroy', $loan))
        ->assertRedirect(route('loans.index', ['direction' => 'given']));

    $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
    expect($account->fresh()->balance)->toBe('10000.00');
});

test('deleting a loan with existing repayments is rejected', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $loan = Loan::factory()->for($user)->given()->create(['account_id' => $account->id]);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loan->id]);

    expect(fn () => app(LoanService::class)->delete($loan))
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('loans', ['id' => $loan->id]);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'given',
            'person_name' => 'Karim',
            'amount' => '0',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertInvalid(['amount']);
});

test('type must be a valid enum value', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'other',
            'person_name' => 'Karim',
            'amount' => '100',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertInvalid(['type']);
});

test('person name is required', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'given',
            'amount' => '100',
            'account_id' => $account->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertInvalid(['person_name']);
});

test('a user cannot attach another users account to their own loan', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    $strangerAccount = Account::factory()->for($stranger)->create();

    $this->actingAs($user)
        ->post(route('loans.store'), [
            'type' => 'given',
            'person_name' => 'Karim',
            'amount' => '100',
            'account_id' => $strangerAccount->id,
            'loan_date' => '2026-08-30',
        ])
        ->assertInvalid(['account_id']);
});

test('a user cannot view, edit, or delete another users loan', function () {
    $owner = User::factory()->create();
    $account = Account::factory()->for($owner)->create();
    $loan = Loan::factory()->for($owner)->create(['account_id' => $account->id]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('loans.show', $loan))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put(route('loans.update', $loan), ['amount' => '1'])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('loans.destroy', $loan))
        ->assertForbidden();
});

test('the index direction filter never mixes given and taken loans', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $given = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'person_name' => 'Given Person']);
    $taken = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'person_name' => 'Taken Person']);

    $this->actingAs($user)
        ->get(route('loans.index', ['direction' => 'given']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('loans/index')
            ->where('direction', 'given')
            ->has('loans.data', 1)
            ->where('loans.data.0.id', $given->id));

    $this->actingAs($user)
        ->get(route('loans.index', ['direction' => 'taken']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('direction', 'taken')
            ->has('loans.data', 1)
            ->where('loans.data.0.id', $taken->id));
});
