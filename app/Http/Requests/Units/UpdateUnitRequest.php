<?php

namespace App\Http\Requests\Units;

use App\Models\Team;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_multiple_of_base') && ! filter_var($this->input('is_multiple_of_base'), FILTER_VALIDATE_BOOLEAN)) {
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
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_name' => ['sometimes', 'required', 'string', 'max:50'],
            'allow_decimal' => ['nullable', 'boolean'],
            'is_multiple_of_base' => ['nullable', 'boolean'],
            'base_unit_id' => [
                Rule::requiredIf(fn () => filter_var($this->input('is_multiple_of_base', $unit->is_multiple_of_base), FILTER_VALIDATE_BOOLEAN)),
                'nullable',
                'integer',
                Rule::exists('units', 'id')->where('team_id', $team->id),
                Rule::notIn([$unit->id]),
            ],
            'base_unit_multiplier' => [
                Rule::requiredIf(fn () => filter_var($this->input('is_multiple_of_base', $unit->is_multiple_of_base), FILTER_VALIDATE_BOOLEAN)),
                'nullable',
                'numeric',
                'min:0.000001',
            ],
        ];
    }
}
