<?php

namespace Database\Factories;

use App\Enums\IncomeSource;
use App\Models\Account;
use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
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
            'source' => fake()->randomElement(IncomeSource::cases()),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'received_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
