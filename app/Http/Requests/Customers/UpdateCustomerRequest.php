<?php

namespace App\Http\Requests\Customers;

use App\Http\Requests\Concerns\ValidatesContactPersonUsers;
use App\Models\Customer;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    use ValidatesContactPersonUsers;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_code' => $this->customer_code === '' ? null : $this->customer_code,
            'pay_term_number' => $this->pay_term_number === '' ? null : $this->pay_term_number,
            'pay_term_type' => $this->pay_term_type === '' ? null : $this->pay_term_type,
            'credit_limit' => $this->credit_limit === '' ? null : $this->credit_limit,
            'customer_group_id' => $this->customer_group_id === '' ? null : $this->customer_group_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');
        /** @var Customer $customer */
        $customer = $this->route('customer');

        return [
            'party_role' => ['sometimes', 'required', 'string', Rule::in(['supplier', 'customer', 'both'])],
            'entity_type' => ['sometimes', 'required', 'string', Rule::in(['individual', 'business'])],
            'customer_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('customers', 'customer_code')
                    ->where('team_id', $team->id)
                    ->ignore($customer->id),
            ],
            'customer_group_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_groups', 'id')->where('team_id', $team->id),
            ],
            'business_name' => [
                Rule::requiredIf($this->input('entity_type', $customer->entity_type) === 'business'),
                'nullable',
                'string',
                'max:255',
            ],
            'prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => [
                Rule::requiredIf($this->input('entity_type', $customer->entity_type) === 'individual'),
                'nullable',
                'string',
                'max:255',
            ],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['sometimes', 'required', 'string', 'max:50'],
            'alternate_number' => ['nullable', 'string', 'max:50'],
            'landline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'dob' => ['nullable', 'date'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'pay_term_number' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'pay_term_type' => ['nullable', 'string', Rule::in(['months', 'days'])],
            'address_line_1' => ['nullable', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:30'],
            'land_mark' => ['nullable', 'string', 'max:255'],
            'street_name' => ['nullable', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:100'],
            'additional_number' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'custom_field1' => ['nullable', 'string', 'max:255'],
            'custom_field2' => ['nullable', 'string', 'max:255'],
            'custom_field3' => ['nullable', 'string', 'max:255'],
            'custom_field4' => ['nullable', 'string', 'max:255'],
            'custom_field5' => ['nullable', 'string', 'max:255'],
            'custom_field6' => ['nullable', 'string', 'max:255'],
            'custom_field7' => ['nullable', 'string', 'max:255'],
            'custom_field8' => ['nullable', 'string', 'max:255'],
            'custom_field9' => ['nullable', 'string', 'max:255'],
            'custom_field10' => ['nullable', 'string', 'max:255'],
            'assigned_to_users' => ['nullable', 'array'],
            'assigned_to_users.*' => [
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $team->id),
            ],
            'contact_persons' => ['nullable', 'array'],
            'contact_persons.*.surname' => ['nullable', 'string', 'max:50'],
            'contact_persons.*.first_name' => ['nullable', 'string', 'max:255'],
            'contact_persons.*.last_name' => ['nullable', 'string', 'max:255'],
            'contact_persons.*.email' => ['nullable', 'email', 'max:255'],
            'contact_persons.*.contact_no' => ['nullable', 'string', 'max:50'],
            'contact_persons.*.alt_number' => ['nullable', 'string', 'max:50'],
            'contact_persons.*.family_number' => ['nullable', 'string', 'max:50'],
            'contact_persons.*.crm_department' => ['nullable', 'string', 'max:100'],
            'contact_persons.*.crm_designation' => ['nullable', 'string', 'max:100'],
            'contact_persons.*.cmmsn_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'contact_persons.*.allow_login' => ['nullable', 'boolean'],
            'contact_persons.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'contact_persons.*.username' => ['nullable', 'string', 'max:255'],
            'contact_persons.*.password' => ['nullable', 'string', 'min:8', 'max:255'],
            'contact_persons.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->withContactPersonUserValidator($validator);
    }
}
