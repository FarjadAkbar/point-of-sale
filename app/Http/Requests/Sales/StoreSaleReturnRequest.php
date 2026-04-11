<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
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
            'parent_sale_id' => ['required', 'integer'],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'discount_type' => ['required', 'in:none,fixed,percentage'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sale_line_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
