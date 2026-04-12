<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_location_id' => $this->business_location_id,
            'customer_id' => $this->customer_id,
            'correspondent_user_id' => $this->correspondent_user_id,
            'restaurant_table_id' => $this->restaurant_table_id,
            'service_staff_user_id' => $this->service_staff_user_id,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'customer_note' => $this->customer_note,
            'send_notification' => $this->send_notification,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'display_name' => $this->customer->display_name,
                'customer_code' => $this->customer->customer_code,
            ]),
            'business_location' => $this->whenLoaded('businessLocation', fn () => [
                'id' => $this->businessLocation->id,
                'name' => $this->businessLocation->name,
            ]),
            'restaurant_table' => $this->whenLoaded('restaurantTable', fn () => $this->restaurantTable
                ? [
                    'id' => $this->restaurantTable->id,
                    'name' => $this->restaurantTable->name,
                ]
                : null),
            'correspondent' => $this->whenLoaded('correspondent', fn () => $this->correspondent
                ? [
                    'id' => $this->correspondent->id,
                    'name' => $this->correspondent->name,
                ]
                : null),
            'service_staff' => $this->whenLoaded('serviceStaff', fn () => $this->serviceStaff
                ? [
                    'id' => $this->serviceStaff->id,
                    'name' => $this->serviceStaff->name,
                ]
                : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
