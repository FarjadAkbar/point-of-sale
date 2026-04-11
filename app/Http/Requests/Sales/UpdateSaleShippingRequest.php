<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shipping_details' => ['nullable', 'string', 'max:65535'],
            'shipping_charges' => ['nullable', 'numeric', 'min:0'],
            'shipping_address' => ['nullable', 'string', 'max:65535'],
            'shipping_status' => ['nullable', 'string', 'max:64'],
            'delivered_to' => ['nullable', 'string', 'max:255'],
            'delivery_person' => ['nullable', 'string', 'max:255'],
            'shipping_customer_note' => ['nullable', 'string', 'max:65535'],
            'shipping_document' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
