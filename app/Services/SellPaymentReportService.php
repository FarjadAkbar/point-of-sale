<?php

namespace App\Services;

use App\Models\SalePayment;
use App\Models\Team;
use App\Support\PaymentMethodLabels;
use Carbon\Carbon;

class SellPaymentReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $customerId,
        ?int $customerGroupId,
        ?string $paymentMethod,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.team_id', $team->id)
            ->whereBetween('sale_payments.paid_on', [$start, $end])
            ->select('sale_payments.*');

        if ($businessLocationId !== null) {
            $q->where('sales.business_location_id', $businessLocationId);
        }
        if ($customerId !== null) {
            $q->where('sales.customer_id', $customerId);
        }
        if ($customerGroupId !== null) {
            $q->where('customers.customer_group_id', $customerGroupId);
        }
        if ($paymentMethod !== null && $paymentMethod !== '') {
            $q->where('sale_payments.method', $paymentMethod);
        }

        $payments = $q
            ->with(['sale.customer.customerGroup', 'sale.businessLocation', 'paymentAccount'])
            ->orderByDesc('sale_payments.paid_on')
            ->orderByDesc('sale_payments.id')
            ->limit(2000)
            ->get();

        $rows = [];
        $sum = 0.0;

        foreach ($payments as $pay) {
            $sale = $pay->sale;
            if (! $sale) {
                continue;
            }
            $customer = $sale->customer;
            $amt = (float) $pay->amount;
            $sum += $amt;

            $rows[] = [
                'payment_id' => $pay->id,
                'reference_no' => 'SP-'.str_pad((string) $pay->id, 6, '0', STR_PAD_LEFT),
                'paid_on' => $pay->paid_on?->toIso8601String() ?? '',
                'amount' => $this->nf($amt),
                'customer' => $customer?->display_name ?? '—',
                'contact_id' => filled($customer?->customer_code) ? (string) $customer->customer_code : '—',
                'customer_group' => $customer?->customerGroup?->name ?? '—',
                'payment_method' => PaymentMethodLabels::label((string) $pay->method),
                'sale_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
                'action_url' => route('sales.detail', ['current_team' => $team->slug, 'sale' => $sale->id]),
            ];
        }

        return [
            'rows' => $rows,
            'footer' => [
                'total_amount' => $this->nf($sum),
            ],
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
