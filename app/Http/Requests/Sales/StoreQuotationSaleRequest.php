<?php

namespace App\Http\Requests\Sales;

use Illuminate\Validation\Rule;

class StoreQuotationSaleRequest extends StoreSaleRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['status' => 'quotation']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        foreach (array_keys($rules) as $key) {
            if ($key === 'payment' || str_starts_with($key, 'payment.')) {
                unset($rules[$key]);
            }
        }

        return array_merge($rules, [
            'status' => ['required', 'string', Rule::in(['quotation'])],
        ]);
    }
}
