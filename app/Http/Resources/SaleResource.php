<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sale
 */
class SaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'status' => $this->status,
            'final_total' => (string) $this->final_total,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'display_name' => $this->customer->display_name,
            ]),
            'business_location' => $this->whenLoaded('businessLocation', fn () => [
                'id' => $this->businessLocation->id,
                'name' => $this->businessLocation->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
