<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanTransfer>
 */
class LoanTransferFactory extends Factory
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
            'account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 500, 5000),
            'transferred_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
