<?php

namespace App\Services\Finance;

use InvalidArgumentException;

/**
 * Immutable decimal money value, always rounded/scaled to 2 places via bcmath.
 *
 * This is the only arithmetic surface financial code in this application
 * should use. Never operate on money as float/int - float arithmetic loses
 * precision and int forces callers to track a scale factor themselves.
 */
final readonly class Money
{
    /**
     * @param  numeric-string  $amount
     */
    private function __construct(private string $amount) {}

    public static function of(string|int|float $amount): self
    {
        return new self(bcadd(self::toNumericString($amount), '0', 2));
    }

    public static function zero(): self
    {
        return self::of(0);
    }

    public function add(self $other): self
    {
        return self::of(bcadd($this->amount, $other->amount, 2));
    }

    public function subtract(self $other): self
    {
        return self::of(bcsub($this->amount, $other->amount, 2));
    }

    public function multiply(string|int $factor): self
    {
        return self::of(bcmul($this->amount, self::toNumericString($factor), 2));
    }

    /**
     * Divide by the given divisor, returning zero instead of throwing when
     * the divisor is zero.
     */
    public function divideOrZero(int $divisor): self
    {
        $divisor = (string) $divisor;

        if (bccomp($divisor, '0', 2) === 0) {
            return self::zero();
        }

        return self::of(bcdiv($this->amount, $divisor, 2));
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', 2) === -1;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', 2) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', 2) === 1;
    }

    /**
     * @return int -1 if this is less than $other, 0 if equal, 1 if greater
     */
    public function compareTo(self $other): int
    {
        return bccomp($this->amount, $other->amount, 2);
    }

    /**
     * @return numeric-string
     */
    public function toDecimalString(): string
    {
        return $this->amount;
    }

    public function toFloat(): float
    {
        return (float) $this->amount;
    }

    /**
     * @return numeric-string
     */
    private static function toNumericString(string|int|float $amount): string
    {
        $amount = (string) $amount;

        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("Invalid money amount: {$amount}");
        }

        return $amount;
    }
}
