<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
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
            'name' => fake()->unique()->randomElement(['Food', 'Grocery', 'Transport', 'Shopping', 'Medicine', 'Electricity', 'Internet', 'Mobile', 'Entertainment', 'Education', 'Other']),
            'icon' => null,
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
