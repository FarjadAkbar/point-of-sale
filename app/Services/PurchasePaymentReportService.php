<?php

namespace App\Services;

use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Team;
use App\Support\PaymentMethodLabels;
use Carbon\Carbon;

class PurchasePaymentReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $supplierId,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = PurchasePayment::query()
            ->join('purchases', 'purchase_payments.purchase_id', '=', 'purchases.id')
            ->where('purchases.team_id', $team->id)
            ->whereBetween('purchase_payments.paid_on', [$start, $end])
            ->select('purchase_payments.*');

        if ($businessLocationId !== null) {
            $q->where('purchases.business_location_id', $businessLocationId);
        }
        if ($supplierId !== null) {
            $q->where('purchases.supplier_id', $supplierId);
        }

        $payments = $q
            ->with(['purchase.supplier', 'purchase.businessLocation', 'paymentAccount'])
            ->orderByDesc('purchase_payments.paid_on')
            ->orderByDesc('purchase_payments.id')
            ->limit(2000)
            ->get();

        $rows = [];
        $sum = 0.0;

        foreach ($payments as $pay) {
            $purchase = $pay->purchase;
            if (! $purchase) {
                continue;
            }
            $amt = (float) $pay->amount;
            $sum += $amt;

            $ref = (string) ($purchase->ref_no ?? '#'.$purchase->id);
            $search = $purchase->ref_no ?? (string) $purchase->id;
            $purchaseUrl = route('purchases.index', ['current_team' => $team->slug]).'?'.http_build_query(['search' => $search]);

            $rows[] = [
                'payment_id' => $pay->id,
                'reference_no' => 'PP-'.str_pad((string) $pay->id, 6, '0', STR_PAD_LEFT),
                'paid_on' => $pay->paid_on?->toIso8601String() ?? '',
                'amount' => $this->nf($amt),
                'supplier' => $this->formatSupplier($purchase->supplier),
                'payment_method' => PaymentMethodLabels::label((string) $pay->method),
                'purchase_ref' => $ref,
                'purchase_url' => $purchaseUrl,
                'action_url' => $purchaseUrl,
            ];
        }

        return [
            'rows' => $rows,
            'footer' => [
                'total_amount' => $this->nf($sum),
            ],
        ];
    }

    protected function formatSupplier(?Supplier $s): string
    {
        if ($s === null) {
            return '—';
        }
        $parts = array_filter([
            trim(implode(' ', array_filter([$s->first_name, $s->last_name]))),
            $s->business_name,
        ]);

        return $parts !== [] ? implode(', ', $parts) : $s->display_name;
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
