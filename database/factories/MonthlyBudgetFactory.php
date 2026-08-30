<?php

namespace Database\Factories;

use App\Models\BudgetCategory;
use App\Models\MonthlyBudget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyBudget>
 */
class MonthlyBudgetFactory extends Factory
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
            'budget_category_id' => BudgetCategory::factory(),
            'year' => now()->year,
            'month' => now()->month,
            'amount' => fake()->randomFloat(2, 1000, 50000),
        ];
    }
}
