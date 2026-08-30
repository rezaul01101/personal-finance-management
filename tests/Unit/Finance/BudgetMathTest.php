<?php

use App\Services\Finance\BudgetMath;
use App\Services\Finance\Money;

// Spec §58 budget table: budget = 15,000
test('available and exceeded status across the budget table', function (string $used, string $available, bool $exceeded, string $over) {
    $budget = Money::of('15000');
    $usedMoney = Money::of($used);

    expect(BudgetMath::available($budget, $usedMoney)->toDecimalString())->toBe($available)
        ->and(BudgetMath::isExceeded($budget, $usedMoney))->toBe($exceeded)
        ->and(BudgetMath::overBudgetAmount($budget, $usedMoney)->toDecimalString())->toBe($over);
})->with([
    'no spend' => ['0', '15000.00', false, '0.00'],
    'partial spend' => ['10000', '5000.00', false, '0.00'],
    'exactly at budget' => ['15000', '0.00', false, '0.00'],
    'one over budget' => ['15001', '-1.00', true, '1.00'],
]);

test('budget exceeded by 800 example from the spec', function () {
    $budget = Money::of('15000');
    $used = Money::of('15800');

    expect(BudgetMath::available($budget, $used)->toDecimalString())->toBe('-800.00')
        ->and(BudgetMath::isExceeded($budget, $used))->toBeTrue()
        ->and(BudgetMath::overBudgetAmount($budget, $used)->toDecimalString())->toBe('800.00');
});

// Spec §6/§58 daily safe spend table
test('daily safe spend across the spec table', function (string $available, int $days, string $expected) {
    expect(BudgetMath::dailySafeSpend(Money::of($available), $days)->toDecimalString())->toBe($expected);
})->with([
    '10,000 over 10 days' => ['10000', 10, '1000.00'],
    '2,600 over 2 days' => ['2600', 2, '1300.00'],
    'zero remaining days' => ['2600', 0, '0.00'],
    'negative remaining days' => ['2600', -1, '0.00'],
]);

test('daily safe spend is zero when the budget is already exceeded, never negative', function () {
    $dailySafeSpend = BudgetMath::dailySafeSpend(Money::of('-800'), 2);

    expect($dailySafeSpend->toDecimalString())->toBe('0.00')
        ->and($dailySafeSpend->isNegative())->toBeFalse();
});

test('usage percentage is zero when the budget itself is zero, avoiding division by zero', function () {
    expect(BudgetMath::usagePercentage(Money::zero(), Money::of('100')))->toBe(0.0);
});

test('usage percentage reflects used over budget', function () {
    // 12400 / 15000 = 0.82666..., bcmath truncates (not rounds) at each scale step.
    expect(BudgetMath::usagePercentage(Money::of('15000'), Money::of('12400')))->toBe(82.66);
});
