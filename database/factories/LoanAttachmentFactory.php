<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanAttachment>
 */
class LoanAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'disk' => 'public',
            'path' => 'loans/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(1000, 500000),
        ];
    }
}
