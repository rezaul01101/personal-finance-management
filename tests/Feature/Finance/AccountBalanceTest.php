<?php

use App\Models\Account;
use App\Services\Finance\AccountBalanceService;
use App\Services\Finance\Money;

test('debit decreases the account balance - spec §58 account table', function () {
    $account = Account::factory()->create(['balance' => 10000]);

    app(AccountBalanceService::class)->debit($account, Money::of('2000'));

    expect($account->fresh()->balance)->toBe('8000.00');
});

test('credit increases the account balance', function () {
    $account = Account::factory()->create(['balance' => 10000]);

    app(AccountBalanceService::class)->credit($account, Money::of('2000'));

    expect($account->fresh()->balance)->toBe('12000.00');
});

test('a debit larger than the balance is allowed and can take the account negative', function () {
    $account = Account::factory()->create(['balance' => 100]);

    app(AccountBalanceService::class)->debit($account, Money::of('500'));

    expect($account->fresh()->balance)->toBe('-400.00');
});

test('the passed-in account instance reflects the new balance without reloading', function () {
    $account = Account::factory()->create(['balance' => 10000]);

    app(AccountBalanceService::class)->debit($account, Money::of('2000'));

    expect($account->balance)->toBe('8000.00');
});
