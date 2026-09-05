<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanRepayment>
 */
class LoanRepaymentFactory extends Factory
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
            'loan_id' => Loan::factory(),
            'account_id' => null,
            'amount' => fake()->randomFloat(2, 500, 5000),
            'repaid_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
