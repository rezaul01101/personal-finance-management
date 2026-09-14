<?php

use App\Enums\SavingsTransactionType;
use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanTransfer;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\Finance\AccountCalculator;

test('an opening balance with no transactions is its own current balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '1000.00']);

    $summary = app(AccountCalculator::class)->summarize($account);

    expect($summary->openingBalance->toDecimalString())->toBe('1000.00')
        ->and($summary->totalCredits->toDecimalString())->toBe('0.00')
        ->and($summary->totalDebits->toDecimalString())->toBe('0.00')
        ->and($summary->currentBalance->toDecimalString())->toBe('1000.00');
});

test('income credits and expense debits the account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '1000.00']);

    Income::factory()->for($user)->create(['account_id' => $account->id, 'amount' => '500.00']);
    Expense::factory()->for($user)->create(['account_id' => $account->id, 'amount' => '200.00']);

    $summary = app(AccountCalculator::class)->summarize($account->fresh());

    expect($summary->totalCredits->toDecimalString())->toBe('500.00')
        ->and($summary->totalDebits->toDecimalString())->toBe('200.00')
        ->and($summary->currentBalance->toDecimalString())->toBe('1300.00');
});

test('an account transfer debits the sender and credits the recipient', function () {
    $user = User::factory()->create();
    $sender = Account::factory()->for($user)->create(['balance' => '1000.00']);
    $recipient = Account::factory()->for($user)->create(['balance' => '500.00']);

    AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $sender->id,
        'to_account_id' => $recipient->id,
        'amount' => '300.00',
    ]);

    $calculator = app(AccountCalculator::class);

    expect($calculator->currentBalance($sender->fresh())->toDecimalString())->toBe('700.00')
        ->and($calculator->currentBalance($recipient->fresh())->toDecimalString())->toBe('800.00');
});

test('a loan given debits the account and a loan taken credits it', function () {
    $user = User::factory()->create();
    $lenderAccount = Account::factory()->for($user)->create(['balance' => '1000.00']);
    $borrowerAccount = Account::factory()->for($user)->create(['balance' => '1000.00']);

    Loan::factory()->for($user)->given()->create(['account_id' => $lenderAccount->id, 'amount' => '400.00']);
    Loan::factory()->for($user)->taken()->create(['account_id' => $borrowerAccount->id, 'amount' => '600.00']);

    $calculator = app(AccountCalculator::class);

    expect($calculator->currentBalance($lenderAccount->fresh())->toDecimalString())->toBe('600.00')
        ->and($calculator->currentBalance($borrowerAccount->fresh())->toDecimalString())->toBe('1600.00');
});

test('a repayment on a loan taken debits the account, but a repayment on a loan given does not touch any account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '1000.00']);

    $loanTaken = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => '800.00']);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loanTaken->id, 'account_id' => $account->id, 'amount' => '300.00']);

    $loanGiven = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => '500.00']);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loanGiven->id, 'account_id' => null, 'amount' => '200.00']);

    $currentBalance = app(AccountCalculator::class)->currentBalance($account->fresh());

    // +800 (loan taken) -300 (its repayment) -500 (loan given); the loan-given
    // repayment never touches an account, it only shrinks the held pool.
    expect($currentBalance->toDecimalString())->toBe('1000.00');
});

test('a loan transfer credits the account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '1000.00']);

    $loanGiven = Loan::factory()->for($user)->given()->create(['account_id' => $account->id, 'amount' => '1000.00']);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loanGiven->id, 'account_id' => null, 'amount' => '600.00']);
    LoanTransfer::factory()->for($user)->create(['loan_id' => $loanGiven->id, 'account_id' => $account->id, 'amount' => '600.00']);

    $currentBalance = app(AccountCalculator::class)->currentBalance($account->fresh());

    // -1000 (loan given) +600 (transferred in from the held pool).
    expect($currentBalance->toDecimalString())->toBe('600.00');
});

test('a savings contribution debits the account and a withdrawal credits it', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '1000.00']);
    $goal = SavingsGoal::factory()->for($user)->create();

    SavingsTransaction::factory()->for($user)->create([
        'savings_goal_id' => $goal->id,
        'account_id' => $account->id,
        'type' => SavingsTransactionType::Contribution,
        'amount' => '300.00',
    ]);
    SavingsTransaction::factory()->for($user)->withdrawal()->create([
        'savings_goal_id' => $goal->id,
        'account_id' => $account->id,
        'amount' => '100.00',
    ]);

    $currentBalance = app(AccountCalculator::class)->currentBalance($account->fresh());

    expect($currentBalance->toDecimalString())->toBe('800.00');
});

test('a combined scenario nets out to the correct current balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create(['balance' => '2000.00']);
    $other = Account::factory()->for($user)->create();

    Income::factory()->for($user)->create(['account_id' => $account->id, 'amount' => '1000.00']);
    Expense::factory()->for($user)->create(['account_id' => $account->id, 'amount' => '400.00']);
    AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $account->id,
        'to_account_id' => $other->id,
        'amount' => '250.00',
    ]);
    AccountTransfer::factory()->for($user)->create([
        'from_account_id' => $other->id,
        'to_account_id' => $account->id,
        'amount' => '150.00',
    ]);
    $loanTaken = Loan::factory()->for($user)->taken()->create(['account_id' => $account->id, 'amount' => '500.00']);
    LoanRepayment::factory()->for($user)->create(['loan_id' => $loanTaken->id, 'account_id' => $account->id, 'amount' => '100.00']);
    $goal = SavingsGoal::factory()->for($user)->create();
    SavingsTransaction::factory()->for($user)->create([
        'savings_goal_id' => $goal->id,
        'account_id' => $account->id,
        'amount' => '200.00',
    ]);

    // 2000 opening + 1000 income + 150 transfer-in + 500 loan taken
    // - 400 expense - 250 transfer-out - 100 repayment - 200 savings contribution
    // = 2700.00
    $currentBalance = app(AccountCalculator::class)->currentBalance($account->fresh());

    expect($currentBalance->toDecimalString())->toBe('2700.00');
});
