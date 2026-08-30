<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\BudgetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetCategory>
 */
class BudgetCategoryFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Family', 'Personal', 'Lending', 'Education', 'Travel', 'Business', 'Other']),
            'icon' => null,
            'description' => fake()->optional()->sentence(),
            'status' => CategoryStatus::Active,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the category is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CategoryStatus::Archived,
        ]);
    }
}
