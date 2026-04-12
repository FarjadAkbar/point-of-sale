<?php

namespace App\Http\Resources;

use App\Models\VariationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VariationTemplate
 */
class VariationTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'sort_order' => $v->sort_order,
            ])->values()->all()),
            'values_count' => isset($this->resource->values_count)
                ? (int) $this->resource->values_count
                : ($this->relationLoaded('values') ? $this->values->count() : 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
