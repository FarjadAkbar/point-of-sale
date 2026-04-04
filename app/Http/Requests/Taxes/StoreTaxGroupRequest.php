<?php

namespace App\Http\Requests\Taxes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxGroupRequest extends FormRequest
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
        $team = $this->route('current_team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_rate_ids' => ['required', 'array', 'min:1'],
            'tax_rate_ids.*' => [
                'integer',
                Rule::exists('tax_rates', 'id')->where('team_id', $team->id),
            ],
        ];
    }
}
