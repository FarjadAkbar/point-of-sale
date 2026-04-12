<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use App\Support\PaymentMethodLabels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellPaymentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $this->input('end_date', now()->toDateString()),
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'business_location_id' => [
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $team->id),
            ],
            'customer_group_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_groups', 'id')->where('team_id', $team->id),
            ],
            'payment_method' => ['nullable', 'string', 'max:32', Rule::in(array_keys(PaymentMethodLabels::options()))],
        ];
    }
}
