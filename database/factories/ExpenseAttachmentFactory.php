<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseAttachment>
 */
class ExpenseAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'disk' => 'public',
            'path' => 'expenses/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(1000, 500000),
        ];
    }
}
