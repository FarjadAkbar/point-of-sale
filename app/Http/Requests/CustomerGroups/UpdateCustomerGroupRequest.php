<?php

namespace App\Http\Requests\CustomerGroups;

use App\Models\CustomerGroup;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->amount === '' ? null : $this->amount,
            'selling_price_group_id' => $this->selling_price_group_id === '' ? null : $this->selling_price_group_id,
        ]);

        $type = $this->input('price_calculation_type');
        if ($type === 'percentage') {
            $this->merge(['selling_price_group_id' => null]);
        } elseif ($type === 'selling_price_group') {
            $this->merge(['amount' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->route('customer_group');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('customer_groups', 'name')
                    ->where('team_id', $team->id)
                    ->ignore($customerGroup->id),
            ],
            'price_calculation_type' => ['sometimes', 'required', 'string', Rule::in(['percentage', 'selling_price_group'])],
            'amount' => [
                Rule::requiredIf($this->input('price_calculation_type', $customerGroup->price_calculation_type) === 'percentage'),
                'nullable',
                'numeric',
            ],
            'selling_price_group_id' => [
                Rule::requiredIf($this->input('price_calculation_type', $customerGroup->price_calculation_type) === 'selling_price_group'),
                'nullable',
                'integer',
                Rule::exists('selling_price_groups', 'id')->where('team_id', $team->id),
            ],
        ];
    }
}
