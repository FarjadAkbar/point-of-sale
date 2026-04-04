<?php

namespace App\Http\Resources;

use App\Support\TaxPercentDisplay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaxRate
 */
class TaxRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount !== null
                ? TaxPercentDisplay::amount($this->amount)
                : null,
            'for_tax_group' => (bool) $this->for_tax_group,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

