<?php

namespace App\Http\Requests\PaymentAccounts;

use App\Models\PaymentAccount;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'redirect_to' => $this->input('redirect_to') ?: 'settings',
        ]);

        foreach (['bank_name', 'account_number', 'notes'] as $k) {
            if ($this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }

        if (in_array($this->input('account_type_id'), ['', null, '__none__'], true)) {
            $this->merge(['account_type_id' => null]);
        }

        $details = $this->input('account_details');
        if (is_array($details)) {
            $clean = [];
            foreach ($details as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));
                if ($label === '' && $value === '') {
                    continue;
                }
                $clean[] = ['label' => $label, 'value' => $value];
            }
            $this->merge(['account_details' => $clean]);
        }

        /** @var PaymentAccount|null $account */
        $account = $this->route('payment_account');
        if ($account instanceof PaymentAccount && $account->payment_method === 'ledger'
            && ($this->input('opening_balance') === '' || $this->input('opening_balance') === null)) {
            $this->merge(['opening_balance' => '0']);
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
        /** @var PaymentAccount $account */
        $account = $this->route('payment_account');

        if ($account->payment_method === 'ledger') {
            return [
                'name' => ['required', 'string', 'max:255'],
                'account_number' => ['required', 'string', 'max:255'],
                'account_type_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('account_types', 'id')->where('team_id', $team->id),
                ],
                'opening_balance' => ['nullable', 'numeric'],
                'account_details' => ['nullable', 'array', 'max:30'],
                'account_details.*.label' => ['nullable', 'string', 'max:255'],
                'account_details.*.value' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:2000'],
                'is_active' => ['sometimes', 'boolean'],
                'redirect_to' => ['required', 'string', Rule::in(['list', 'settings'])],
            ];
        }

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
            'redirect_to' => ['required', 'string', Rule::in(['list', 'settings'])],
        ];
    }
}
