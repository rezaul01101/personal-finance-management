<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\CategoryStatus;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
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
            'name' => fake()->randomElement(['Cash', 'Bank', 'bKash', 'Nagad']),
            'type' => fake()->randomElement(AccountType::cases()),
            'balance' => fake()->randomFloat(2, 0, 100000),
            'status' => CategoryStatus::Active,
        ];
    }

    /**
     * Indicate that the account is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CategoryStatus::Archived,
        ]);
    }
}
