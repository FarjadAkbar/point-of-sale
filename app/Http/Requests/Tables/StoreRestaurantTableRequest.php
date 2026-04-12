<?php

namespace App\Http\Requests\Tables;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantTableRequest extends FormRequest
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
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'business_location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('restaurant_tables', 'name')->where(
                    fn ($q) => $q->where(
                        'business_location_id',
                        (int) $this->input('business_location_id')
                    )
                ),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
