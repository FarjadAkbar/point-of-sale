<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CustomerGroup
 */
class CustomerGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price_calculation_type' => $this->price_calculation_type,
            'amount' => $this->amount,
            'selling_price_group_id' => $this->selling_price_group_id,
            'selling_price_group' => $this->whenLoaded('sellingPriceGroup', fn () => $this->sellingPriceGroup ? [
                'id' => $this->sellingPriceGroup->id,
                'name' => $this->sellingPriceGroup->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
