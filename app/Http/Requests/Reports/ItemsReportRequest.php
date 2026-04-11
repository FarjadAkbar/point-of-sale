<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sell_start_date' => $this->input('sell_start_date', now()->startOfMonth()->toDateString()),
            'sell_end_date' => $this->input('sell_end_date', now()->toDateString()),
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
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('team_id', $team->id),
            ],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $team->id),
            ],
            'purchase_start_date' => ['nullable', 'date'],
            'purchase_end_date' => ['nullable', 'date', 'after_or_equal:purchase_start_date'],
            'sell_start_date' => ['required', 'date'],
            'sell_end_date' => ['required', 'date', 'after_or_equal:sell_start_date'],
        ];
    }
}
