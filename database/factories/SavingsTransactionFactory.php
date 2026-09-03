<?php

namespace Database\Factories;

use App\Enums\SavingsTransactionType;
use App\Models\Account;
use App\Models\SavingsGoal;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsTransaction>
 */
class SavingsTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'savings_goal_id' => SavingsGoal::factory(),
            'account_id' => Account::factory(),
            'type' => SavingsTransactionType::Contribution,
            'amount' => fake()->randomFloat(2, 500, 20000),
            'transacted_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that this transaction is a withdrawal.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SavingsTransactionType::Withdrawal,
        ]);
    }
}
