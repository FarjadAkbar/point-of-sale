<?php

namespace App\Http\Resources;

use App\Models\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessLocation
 */
class BusinessLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location_id' => $this->location_id,
            'landmark' => $this->landmark,
            'city' => $this->city,
            'zip_code' => $this->zip_code,
            'state' => $this->state,
            'country' => $this->country,
            'mobile' => $this->mobile,
            'alternate_contact_number' => $this->alternate_contact_number,
            'email' => $this->email,
            'website' => $this->website,
            'default_selling_price_group_id' => $this->default_selling_price_group_id,
            'featured_product_ids' => $this->featured_product_ids ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
