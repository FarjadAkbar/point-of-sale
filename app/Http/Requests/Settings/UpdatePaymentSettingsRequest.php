<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePaymentSettingsRequest extends FormRequest
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
            'cash_enabled' => ['required', 'boolean'],
            'bank_transfer_enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->boolean('cash_enabled') && ! $this->boolean('bank_transfer_enabled')) {
                $v->errors()->add('cash_enabled', 'At least one payment method must stay enabled.');
            }
        });
    }

    /**
     * @return array<string, bool>
     */
    public function paymentSettingsPayload(): array
    {
        return [
            'cash_enabled' => $this->boolean('cash_enabled'),
            'bank_transfer_enabled' => $this->boolean('bank_transfer_enabled'),
        ];
    }
}
