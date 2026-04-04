<?php

namespace App\Http\Requests\Units;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! filter_var($this->input('is_multiple_of_base'), FILTER_VALIDATE_BOOLEAN)) {
            $this->merge([
                'is_multiple_of_base' => false,
                'base_unit_id' => null,
                'base_unit_multiplier' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'allow_decimal' => ['nullable', 'boolean'],
            'is_multiple_of_base' => ['nullable', 'boolean'],
            'base_unit_id' => [
                Rule::requiredIf(fn () => filter_var($this->input('is_multiple_of_base'), FILTER_VALIDATE_BOOLEAN)),
                'nullable',
                'integer',
                Rule::exists('units', 'id')->where('team_id', $team->id),
            ],
            'base_unit_multiplier' => [
                Rule::requiredIf(fn () => filter_var($this->input('is_multiple_of_base'), FILTER_VALIDATE_BOOLEAN)),
                'nullable',
                'numeric',
                'min:0.000001',
            ],
        ];
    }
}
