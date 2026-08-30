<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountTransfer>
 */
class AccountTransferFactory extends Factory
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
            'from_account_id' => Account::factory(),
            'to_account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 100, 20000),
            'transferred_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
