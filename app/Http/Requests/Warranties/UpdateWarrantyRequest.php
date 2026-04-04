<?php

namespace App\Http\Requests\Warranties;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarrantyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duration_value' => ['sometimes', 'required', 'integer', 'min:1', 'max:999999'],
            'duration_unit' => ['sometimes', 'required', 'string', Rule::in(['day', 'week', 'month', 'year'])],
        ];
    }
}
