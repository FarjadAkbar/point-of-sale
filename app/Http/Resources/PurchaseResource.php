<?php

namespace App\Http\Resources;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ref_no' => $this->ref_no,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'status' => $this->status,
            'final_total' => (string) $this->final_total,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'display_name' => $this->supplier->display_name,
            ]),
            'business_location' => $this->whenLoaded('businessLocation', fn () => [
                'id' => $this->businessLocation->id,
                'name' => $this->businessLocation->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
