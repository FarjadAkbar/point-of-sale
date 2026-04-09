<?php

namespace App\Http\Requests\Sales;

use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Team;
use App\Services\ProductStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('lines'))) {
            $decoded = json_decode($this->input('lines'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['lines' => $decoded]);
            }
        }

        if (is_string($this->input('additional_expenses'))) {
            $decoded = json_decode($this->input('additional_expenses'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['additional_expenses' => $decoded]);
            }
        }

        if (is_string($this->input('payment'))) {
            $decoded = json_decode($this->input('payment'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['payment' => $decoded]);
            }
        }

        foreach (['invoice_no', 'shipping_details', 'shipping_address', 'sale_note'] as $k) {
            if ($this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }

        if (in_array($this->input('tax_rate_id'), ['', null, '__none__'], true)) {
            $this->merge(['tax_rate_id' => null]);
        }

        if ($this->input('pay_term_number') === '' || $this->input('pay_term_number') === null) {
            $this->merge(['pay_term_number' => null, 'pay_term_type' => null]);
        }

        $ptt = $this->input('pay_term_type');
        if ($ptt === '' || $ptt === '__none__' || $ptt === null) {
            $this->merge(['pay_term_type' => null]);
        }

        $pay = $this->input('payment');
        if (is_array($pay) && array_key_exists('payment_account_id', $pay) && $pay['payment_account_id'] === '') {
            $pay['payment_account_id'] = null;
            $this->merge(['payment' => $pay]);
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
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('team_id', $team->id)],
            'business_location_id' => ['required', 'integer', Rule::exists('business_locations', 'id')->where('team_id', $team->id)],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in(['final', 'draft', 'quotation', 'proforma'])],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'string', Rule::in(['months', 'days'])],
            'discount_type' => ['required', 'string', Rule::in(['none', 'fixed', 'percentage'])],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')->where('team_id', $team->id)],
            'shipping_details' => ['nullable', 'string', 'max:500'],
            'shipping_charges' => ['required', 'numeric', 'min:0'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'additional_expenses' => ['nullable', 'array', 'max:20'],
            'additional_expenses.*.name' => ['nullable', 'string', 'max:255'],
            'additional_expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'sale_note' => ['nullable', 'string', 'max:10000'],
            'document' => ['nullable', 'file', 'max:5120', 'mimes:pdf,csv,zip,doc,docx,jpeg,jpg,png'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price_before_discount' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.product_tax_percent' => ['nullable', 'numeric', 'min:0'],
            'payment' => ['required', 'array'],
            'payment.amount' => ['required', 'numeric', 'min:0'],
            'payment.paid_on' => ['required', 'date'],
            'payment.method' => ['required', 'string', Rule::in(['cash', 'bank_transfer'])],
            'payment.payment_account_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_accounts', 'id')->where('team_id', $team->id),
            ],
            'payment.note' => ['nullable', 'string', 'max:5000'],
            'payment.bank_account_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Team $team */
            $team = $this->route('current_team');

            if ($this->input('status') !== 'quotation') {
                $settings = $team->resolvedPaymentSettings();
                $method = $this->input('payment.method');

                if ($method === 'cash' && ! $settings['cash_enabled']) {
                    $v->errors()->add('payment.method', 'Cash payments are disabled for this business.');
                }

                if ($method === 'bank_transfer' && ! $settings['bank_transfer_enabled']) {
                    $v->errors()->add('payment.method', 'Bank transfer payments are disabled for this business.');
                }

                $accountId = $this->input('payment.payment_account_id');
                if ($accountId) {
                    $account = PaymentAccount::query()
                        ->forTeam($team)
                        ->whereKey($accountId)
                        ->first();
                    if (! $account || ! $account->is_active) {
                        $v->errors()->add('payment.payment_account_id', 'Invalid payment account.');

                        return;
                    }
                    if ($account->payment_method !== $method) {
                        $v->errors()->add('payment.payment_account_id', 'The account does not match the payment method.');
                    }
                }
            }

            $locId = (int) $this->input('business_location_id');
            $lines = $this->input('lines', []);
            if (! is_array($lines) || $locId < 1) {
                return;
            }

            $stock = app(ProductStockService::class);

            foreach ($lines as $i => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $pid = (int) ($line['product_id'] ?? 0);
                if ($pid < 1) {
                    continue;
                }

                $atLocation = Product::query()
                    ->forTeam($team)
                    ->whereKey($pid)
                    ->forBusinessLocation($locId)
                    ->exists();

                if (! $atLocation) {
                    $v->errors()->add(
                        "lines.$i.product_id",
                        'This product is not available at the selected business location.',
                    );
                }

                if ($this->input('status') === 'final') {
                    $product = Product::query()->forTeam($team)->whereKey($pid)->first();
                    if ($product && $product->manage_stock) {
                        $need = (float) ($line['quantity'] ?? 0);
                        $have = $stock->quantityAt($pid, $locId);
                        if ($have < $need) {
                            $v->errors()->add(
                                "lines.$i.quantity",
                                'Insufficient stock at this location for '.$product->name.'.',
                            );
                        }
                    }
                }
            }
        });
    }
}
