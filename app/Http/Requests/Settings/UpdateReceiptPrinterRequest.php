<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptPrinterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'connection_type' => [
                'required',
                'string',
                Rule::in(['network', 'windows', 'linux']),
            ],
        ];
    }
}
