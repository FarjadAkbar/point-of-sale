<?php

namespace App\Http\Requests\Sales;

use Illuminate\Validation\Rule;

class StoreDraftSaleRequest extends StoreSaleRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge(['status' => 'draft']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['required', 'string', Rule::in(['draft'])],
            'lines' => ['nullable', 'array'],
        ]);
    }
}
