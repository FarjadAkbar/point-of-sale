<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockReportRequest extends FormRequest
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
        ];
    }
}
