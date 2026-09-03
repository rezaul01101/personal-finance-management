<?php

namespace App\Http\Requests\Finance;

use App\Enums\SavingsGoalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavingsGoalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'target_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(SavingsGoalStatus::class)],
        ];
    }
}
