<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrendingProductsReportRequest extends FormRequest
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
            'limit' => $this->input('limit', 5),
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
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
            'business_location_id' => [
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('team_id', $team->id),
            ],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('team_id', $team->id),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where('team_id', $team->id),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                Rule::exists('units', 'id')->where('team_id', $team->id),
            ],
            'product_type' => ['nullable', 'string', Rule::in(['', 'single', 'variable', 'combo'])],
        ];
    }
}
