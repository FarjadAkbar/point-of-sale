<?php

namespace App\Http\Requests\PaymentAccounts;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['bank_name', 'account_number', 'notes'] as $k) {
            if ($this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');
        $settings = $team->resolvedPaymentSettings();

        return [
            'name' => ['required', 'string', 'max:255'],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['cash', 'bank_transfer']),
                function (string $attribute, mixed $value, \Closure $fail) use ($settings) {
                    if ($value === 'cash' && ! $settings['cash_enabled']) {
                        $fail('Cash is disabled; enable it under payment settings first.');
                    }
                    if ($value === 'bank_transfer' && ! $settings['bank_transfer_enabled']) {
                        $fail('Bank transfer is disabled; enable it under payment settings first.');
                    }
                },
            ],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
