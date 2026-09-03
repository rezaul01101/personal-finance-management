<?php

namespace Database\Factories;

use App\Enums\SavingsGoalStatus;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
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
            'name' => fake()->randomElement(['Emergency Fund', 'Travel', 'New Laptop', 'Wedding']),
            'target_amount' => fake()->randomFloat(2, 10000, 500000),
            'target_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
            'description' => fake()->optional()->sentence(),
            'status' => SavingsGoalStatus::Active,
        ];
    }

    /**
     * Indicate that the goal is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SavingsGoalStatus::Completed,
        ]);
    }

    /**
     * Indicate that the goal is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SavingsGoalStatus::Archived,
        ]);
    }
}
