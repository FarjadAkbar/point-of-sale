<?php

namespace App\Http\Requests\Expenses;

use App\Models\PaymentAccount;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('payments'))) {
            $decoded = json_decode($this->input('payments'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['payments' => $decoded]);
            }
        }

        foreach (['ref_no', 'additional_notes'] as $k) {
            if ($this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }

        if (in_array($this->input('tax_rate_id'), ['', null, '__none__'], true)) {
            $this->merge(['tax_rate_id' => null]);
        }

        if (in_array($this->input('expense_category_id'), ['', null, '__none__'], true)) {
            $this->merge(['expense_category_id' => null]);
        }

        if (in_array($this->input('expense_for_user_id'), ['', null, '__none__'], true)) {
            $this->merge(['expense_for_user_id' => null]);
        }

        if (in_array($this->input('contact_id'), ['', null, '__none__'], true)) {
            $this->merge(['contact_id' => null]);
        }

        $this->merge([
            'is_refund' => $this->boolean('is_refund'),
            'is_recurring' => $this->boolean('is_recurring'),
        ]);

        if (! $this->boolean('is_recurring')) {
            $this->merge([
                'recur_interval' => null,
                'recur_interval_type' => null,
                'recur_repetitions' => null,
                'subscription_repeat_on' => null,
            ]);
        }

        $payments = $this->input('payments');
        if (is_array($payments)) {
            foreach ($payments as $i => $row) {
                if (is_array($row) && array_key_exists('payment_account_id', $row) && $row['payment_account_id'] === '') {
                    $payments[$i]['payment_account_id'] = null;
                }
            }
            $this->merge(['payments' => $payments]);
        }

        if ($this->input('recur_repetitions') === '') {
            $this->merge(['recur_repetitions' => null]);
        }

        if (in_array($this->input('subscription_repeat_on'), ['', null, '__none__'], true)) {
            $this->merge(['subscription_repeat_on' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'business_location_id' => ['required', 'integer', Rule::exists('business_locations', 'id')->where('team_id', $team->id)],
            'expense_category_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->where('team_id', $team->id),
            ],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'expense_for_user_id' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $team->id),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $team->id),
            ],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png'],
            'tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
            'final_total' => ['required', 'numeric', 'min:0.0001'],
            'additional_notes' => ['nullable', 'string', 'max:10000'],
            'is_refund' => ['boolean'],
            'is_recurring' => ['boolean'],
            'recur_interval' => ['nullable', 'integer', 'min:1'],
            'recur_interval_type' => ['nullable', 'string', Rule::in(['days', 'months', 'years'])],
            'recur_repetitions' => ['nullable', 'integer', 'min:1'],
            'subscription_repeat_on' => ['nullable', 'integer', 'min:1', 'max:30'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.paid_on' => ['required', 'date'],
            'payments.*.method' => ['required', 'string', Rule::in(['cash', 'bank_transfer'])],
            'payments.*.payment_account_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_accounts', 'id')->where('team_id', $team->id),
            ],
            'payments.*.note' => ['nullable', 'string', 'max:5000'],
            'payments.*.bank_account_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->boolean('is_recurring')) {
                if (! $this->filled('recur_interval')) {
                    $v->errors()->add('recur_interval', 'Recurring interval is required when recurring is enabled.');
                }
                if (! $this->filled('recur_interval_type')) {
                    $v->errors()->add('recur_interval_type', 'Recurring interval type is required when recurring is enabled.');
                }
            }

            $payments = $this->input('payments');
            if (! is_array($payments)) {
                return;
            }

            /** @var Team $team */
            $team = $this->route('current_team');
            $final = round((float) $this->input('final_total'), 4);
            $sum = 0.0;
            foreach ($payments as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $sum += round((float) ($row['amount'] ?? 0), 4);
                $accId = $row['payment_account_id'] ?? null;
                if ($accId) {
                    $acc = PaymentAccount::query()->forTeam($team)->whereKey($accId)->first();
                    if ($acc && $acc->payment_method !== ($row['method'] ?? '')) {
                        $v->errors()->add("payments.$i.payment_account_id", 'Payment account does not match the selected method.');
                    }
                }
            }

            if (abs($sum - $final) > 0.02) {
                $v->errors()->add('payments', 'The sum of payment amounts must equal the total amount.');
            }
        });
    }
}
