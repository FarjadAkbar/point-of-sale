<?php

namespace App\Http\Resources;

use App\Support\TaxPercentDisplay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaxGroup
 */
class TaxGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rates = $this->relationLoaded('taxRates')
            ? $this->taxRates
            : collect();

        $combined = $rates->isEmpty()
            ? '0'
            : TaxPercentDisplay::amount($rates->sum(fn ($r) => (float) $r->amount));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'tax_rates' => TaxRateResource::collection($rates)->resolve(),
            'tax_rate_ids' => $rates->pluck('id')->values()->all(),
            'sub_taxes_label' => $rates->isEmpty()
                ? ''
                : $rates->map(fn ($r) => TaxPercentDisplay::rateLine($r))->implode(', '),
            'combined_rate_percent' => $combined,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
