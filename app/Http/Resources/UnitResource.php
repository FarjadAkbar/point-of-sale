<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Unit
 */
class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'allow_decimal' => (bool) $this->allow_decimal,
            'is_multiple_of_base' => (bool) $this->is_multiple_of_base,
            'base_unit_id' => $this->base_unit_id,
            'base_unit_multiplier' => $this->base_unit_multiplier !== null
                ? (string) $this->base_unit_multiplier
                : null,
            'base_unit' => $this->whenLoaded('baseUnit', fn () => $this->baseUnit ? [
                'id' => $this->baseUnit->id,
                'name' => $this->baseUnit->name,
                'short_name' => $this->baseUnit->short_name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
