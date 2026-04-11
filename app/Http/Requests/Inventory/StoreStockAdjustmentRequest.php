<?php

namespace App\Http\Requests\Inventory;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
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
            'ref_no' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'string', 'in:normal,abnormal'],
            'total_amount_recovered' => ['nullable', 'numeric', 'min:0'],
            'additional_notes' => ['nullable', 'string', 'max:65535'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('team_id', $team->id),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'not_in:0'],
            'lines.*.unit_price' => ['required', 'numeric'],
        ];
    }
}
