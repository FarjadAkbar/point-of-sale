<?php

namespace App\Http\Resources;

use App\Models\ModifierSet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModifierSet
 */
class ModifierSetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (string) $item->price,
                'sort_order' => $item->sort_order,
            ])->values()->all()),
            'items_count' => isset($this->resource->items_count)
                ? (int) $this->resource->items_count
                : ($this->relationLoaded('items') ? $this->items->count() : 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
