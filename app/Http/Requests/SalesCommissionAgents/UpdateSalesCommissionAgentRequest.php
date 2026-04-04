<?php

namespace App\Http\Requests\SalesCommissionAgents;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesCommissionAgentRequest extends FormRequest
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
            'prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:5000'],
            'cmmsn_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
