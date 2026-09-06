<?php

namespace App\Http\Requests;

use App\Concerns\SanitizesNoteHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    use SanitizesNoteHtml;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : null,
            'description' => $this->sanitizeNoteHtml($this->input('description')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:150', 'required_without:description'],
            'description' => ['nullable', 'string', 'max:10000', 'required_without:title'],
        ];
    }
}
