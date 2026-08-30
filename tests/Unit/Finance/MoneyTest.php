<?php

use App\Services\Finance\Money;

test('add sums two amounts precisely', function () {
    expect(Money::of('10.10')->add(Money::of('5.05'))->toDecimalString())->toBe('15.15');
});

test('subtract can go negative', function () {
    expect(Money::of('100')->subtract(Money::of('150'))->toDecimalString())->toBe('-50.00');
});

test('multiply by a negative factor negates the amount', function () {
    expect(Money::of('500')->multiply('-1')->toDecimalString())->toBe('-500.00');
});

test('divideOrZero divides normally', function () {
    expect(Money::of('2600')->divideOrZero(2)->toDecimalString())->toBe('1300.00');
});

test('divideOrZero returns zero instead of dividing by zero', function () {
    expect(Money::of('2600')->divideOrZero(0)->toDecimalString())->toBe('0.00');
});

test('isNegative/isZero/isPositive report the correct sign', function () {
    expect(Money::of('-1')->isNegative())->toBeTrue()
        ->and(Money::of('0')->isZero())->toBeTrue()
        ->and(Money::of('1')->isPositive())->toBeTrue();
});

test('compareTo orders amounts correctly', function () {
    expect(Money::of('10')->compareTo(Money::of('5')))->toBe(1)
        ->and(Money::of('5')->compareTo(Money::of('10')))->toBe(-1)
        ->and(Money::of('5')->compareTo(Money::of('5')))->toBe(0);
});

test('amounts are always scaled to 2 decimal places with no float drift', function () {
    // 0.1 + 0.2 famously does not equal 0.3 in binary float arithmetic.
    expect(Money::of('0.10')->add(Money::of('0.20'))->toDecimalString())->toBe('0.30');
});
