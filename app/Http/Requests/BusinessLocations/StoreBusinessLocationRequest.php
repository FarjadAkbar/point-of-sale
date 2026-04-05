<?php

namespace App\Http\Requests\BusinessLocations;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'location_id',
            'landmark',
            'city',
            'zip_code',
            'state',
            'country',
            'mobile',
            'alternate_contact_number',
            'email',
            'website',
        ] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }

        if ($this->input('default_selling_price_group_id') === '' || $this->input('default_selling_price_group_id') === null) {
            $this->merge(['default_selling_price_group_id' => null]);
        }

        $v = $this->input('featured_product_ids');
        if (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['featured_product_ids' => $decoded]);
            }
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
            'location_id' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:32'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:64'],
            'alternate_contact_number' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:2048', 'url'],
            'default_selling_price_group_id' => [
                'nullable',
                'integer',
                Rule::exists('selling_price_groups', 'id')->where('team_id', $team->id),
            ],
            'featured_product_ids' => ['nullable', 'array'],
            'featured_product_ids.*' => [
                'integer',
                Rule::exists('products', 'id')->where('team_id', $team->id),
            ],
        ];
    }
}
