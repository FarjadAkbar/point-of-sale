<?php

namespace App\Http\Requests\TeamUsers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamUserRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'username' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_enable_service_staff_pin' => ['sometimes', 'boolean'],
            'service_staff_pin' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('is_enable_service_staff_pin')),
                'string',
                'min:4',
            ],
            'allow_login' => ['sometimes', 'boolean'],
            'pos_role_id' => [
                'required',
                'integer',
                Rule::exists('pos_roles', 'id')->where('team_id', $team->id),
            ],
            'access_all_locations' => ['sometimes', 'boolean'],
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => ['integer'],
            'cmmsn_percent' => ['nullable', 'numeric'],
            'max_sales_discount_percent' => ['nullable', 'numeric'],
            'selected_contacts' => ['sometimes', 'boolean'],
            'profile' => ['sometimes', 'array'],
        ];
    }
}
