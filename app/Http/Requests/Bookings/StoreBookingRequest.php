<?php

namespace App\Http\Requests\Bookings;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['correspondent_user_id', 'restaurant_table_id', 'service_staff_user_id'] as $key) {
            $v = $this->input($key);
            if ($v === '__none__' || $v === '' || $v === null) {
                $this->merge([$key => null]);
            }
        }

        $this->merge([
            'correspondent_user_id' => $this->filled('correspondent_user_id')
                ? (int) $this->correspondent_user_id
                : null,
            'restaurant_table_id' => $this->filled('restaurant_table_id')
                ? (int) $this->restaurant_table_id
                : null,
            'service_staff_user_id' => $this->filled('service_staff_user_id')
                ? (int) $this->service_staff_user_id
                : null,
            'send_notification' => $this->boolean('send_notification'),
        ]);
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $team->id),
            ],
            'correspondent_user_id' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $team->id),
            ],
            'restaurant_table_id' => [
                'nullable',
                'integer',
                Rule::exists('restaurant_tables', 'id')
                    ->where('team_id', $team->id)
                    ->where('business_location_id', (int) $this->input('business_location_id')),
            ],
            'service_staff_user_id' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $team->id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'customer_note' => ['nullable', 'string', 'max:5000'],
            'send_notification' => ['boolean'],
        ];
    }
}
