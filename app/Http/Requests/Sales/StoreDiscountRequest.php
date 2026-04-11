<?php

namespace App\Http\Requests\Sales;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => [
                'integer',
                Rule::exists('products', 'id')->where('team_id', $team->id),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where('team_id', $team->id),
            ],
            'product_category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('team_id', $team->id),
            ],
            'business_location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'priority' => ['required', 'integer', 'min:0', 'max:999999'],
            'discount_type' => ['required', 'in:fixed,percentage'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'selling_price_group_id' => [
                'nullable',
                'integer',
                Rule::exists('selling_price_groups', 'id')->where('team_id', $team->id),
            ],
            'applicable_in_customer_groups' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, business_location_id: int, brand_id: int|null, product_category_id: int|null, priority: int, discount_type: string, discount_amount: float, starts_at: string|null, ends_at: string|null, selling_price_group_id: int|null, applicable_in_customer_groups: bool, is_active: bool, product_ids: list<int>}
     */
    public function discountPayload(): array
    {
        $validated = $this->validated();

        $productIds = array_values(array_unique(array_map('intval', $validated['product_ids'] ?? [])));

        return [
            'name' => $validated['name'],
            'business_location_id' => (int) $validated['business_location_id'],
            'brand_id' => isset($validated['brand_id']) ? (int) $validated['brand_id'] : null,
            'product_category_id' => isset($validated['product_category_id']) ? (int) $validated['product_category_id'] : null,
            'priority' => (int) $validated['priority'],
            'discount_type' => $validated['discount_type'],
            'discount_amount' => round((float) $validated['discount_amount'], 4),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'selling_price_group_id' => isset($validated['selling_price_group_id'])
                ? (int) $validated['selling_price_group_id']
                : null,
            'applicable_in_customer_groups' => (bool) ($validated['applicable_in_customer_groups'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'product_ids' => $productIds,
        ];
    }
}
