<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'party_role' => $this->party_role,
            'entity_type' => $this->entity_type,
            'customer_code' => $this->customer_code,
            'customer_group_id' => $this->customer_group_id,
            'customer_group' => $this->whenLoaded('customerGroup', fn () => $this->customerGroup ? [
                'id' => $this->customerGroup->id,
                'name' => $this->customerGroup->name,
            ] : null),
            'business_name' => $this->business_name,
            'prefix' => $this->prefix,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'mobile' => $this->mobile,
            'alternate_number' => $this->alternate_number,
            'landline' => $this->landline,
            'email' => $this->email,
            'dob' => $this->dob?->format('Y-m-d'),
            'tax_number' => $this->tax_number,
            'opening_balance' => $this->opening_balance,
            'credit_limit' => $this->credit_limit,
            'pay_term_number' => $this->pay_term_number,
            'pay_term_type' => $this->pay_term_type,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'land_mark' => $this->land_mark,
            'street_name' => $this->street_name,
            'building_number' => $this->building_number,
            'additional_number' => $this->additional_number,
            'shipping_address' => $this->shipping_address,
            'custom_field1' => $this->custom_field1,
            'custom_field2' => $this->custom_field2,
            'custom_field3' => $this->custom_field3,
            'custom_field4' => $this->custom_field4,
            'custom_field5' => $this->custom_field5,
            'custom_field6' => $this->custom_field6,
            'custom_field7' => $this->custom_field7,
            'custom_field8' => $this->custom_field8,
            'custom_field9' => $this->custom_field9,
            'custom_field10' => $this->custom_field10,
            'assigned_to_users' => $this->whenLoaded('assignedUsers', fn () => $this->assignedUsers->pluck('id')->values()->all()),
            'assigned_users' => $this->whenLoaded('assignedUsers', fn () => $this->assignedUsers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])),
            'contact_persons' => $this->whenLoaded('contactPersons', fn () => $this->contactPersons->map(fn ($p) => [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'surname' => $p->surname,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'email' => $p->email,
                'contact_no' => $p->contact_no,
                'alt_number' => $p->alt_number,
                'family_number' => $p->family_number,
                'crm_department' => $p->crm_department,
                'crm_designation' => $p->crm_designation,
                'cmmsn_percent' => $p->cmmsn_percent,
                'allow_login' => $p->allow_login,
                'username' => $p->username,
                'is_active' => $p->is_active,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
