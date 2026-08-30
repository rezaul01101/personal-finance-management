<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\BudgetCategory;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'expense_category_id' => ExpenseCategory::factory(),
            'budget_category_id' => BudgetCategory::factory(),
            'account_id' => Account::factory(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'spent_on' => now()->toDateString(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
