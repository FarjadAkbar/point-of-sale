<?php

namespace App\Http\Requests\PosRoles;

use App\Support\PosPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosRoleRequest extends FormRequest
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
        $checkboxKeys = PosPermissionCatalog::allCheckboxKeys();
        $radioKeys = array_keys(PosPermissionCatalog::defaultRadioSelections());
        $radioValues = PosPermissionCatalog::allRadioValues();

        return [
            'name' => ['required', 'string', 'max:255'],
            'is_service_staff' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'max:255', Rule::in($checkboxKeys)],
            'radio_options' => ['sometimes', 'array'],
            'radio_options.*' => ['nullable', 'string', 'max:255', Rule::in($radioValues)],
            ...collect($radioKeys)->mapWithKeys(fn (string $key) => [
                "radio_options.$key" => ['nullable', 'string', Rule::in($radioValues)],
            ])->all(),
        ];
    }
}
