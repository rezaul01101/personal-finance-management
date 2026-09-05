<?php

namespace Database\Factories;

use App\Enums\LoanType;
use App\Models\Account;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
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
            'account_id' => Account::factory(),
            'type' => fake()->randomElement(LoanType::cases()),
            'person_name' => fake()->name(),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'loan_date' => now()->toDateString(),
            'expected_return_date' => fake()->optional()->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Money the user lent to someone else.
     */
    public function given(): static
    {
        return $this->state(['type' => LoanType::Given]);
    }

    /**
     * Money the user borrowed from someone else.
     */
    public function taken(): static
    {
        return $this->state(['type' => LoanType::Taken]);
    }
}
