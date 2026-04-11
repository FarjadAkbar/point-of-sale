<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\TaxRate;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxReportService
{
    public function __construct(
        protected SaleService $saleService,
        protected PurchaseService $purchaseService,
    ) {}

    /**
     * @return array{
     *     tax_rates: list<array{id: int, key: string, label: string}>,
     *     summary: array<string, string>,
     *     input_rows: list<array<string, mixed>>,
     *     output_rows: list<array<string, mixed>>,
     *     expense_rows: list<array<string, mixed>>,
     *     input_column_totals: array<string, string>,
     *     output_column_totals: array<string, string>,
     *     expense_column_totals: array<string, string>,
     * }
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId, ?int $customerId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $rates = TaxRate::query()
            ->forTeam($team)
            ->orderBy('name')
            ->orderBy('amount')
            ->get();

        $taxRatesMeta = $rates->map(function (TaxRate $r) {
            $pctStr = rtrim(rtrim(number_format((float) $r->amount, 4, '.', ''), '0'), '.') ?: '0';

            return [
                'id' => $r->id,
                'key' => 'tax_'.$r->id,
                'label' => $r->name.'@'.$pctStr.'%',
            ];
        })->values()->all();

        $rateIds = $rates->pluck('id')->all();

        $inputRows = $this->buildPurchaseTaxRows($team, $start, $end, $businessLocationId, $rates, $rateIds);
        $outputRows = $this->buildSaleTaxRows($team, $start, $end, $businessLocationId, $customerId, $rates, $rateIds);
        $expenseRows = $this->buildExpenseTaxRows($team, $start, $end, $businessLocationId, $customerId, $rates, $rateIds);

        $inputColumnTotals = $this->sumTaxColumns($inputRows, $rateIds);
        $outputColumnTotals = $this->sumTaxColumns($outputRows, $rateIds);
        $expenseColumnTotals = $this->sumTaxColumns($expenseRows, $rateIds);

        $inputTaxTotal = array_sum(array_map('floatval', $inputColumnTotals));
        $outputTaxTotal = array_sum(array_map('floatval', $outputColumnTotals));
        $expenseTaxTotal = array_sum(array_map('floatval', $expenseColumnTotals));
        $taxDifference = $outputTaxTotal - $inputTaxTotal - $expenseTaxTotal;

        return [
            'tax_rates' => $taxRatesMeta,
            'summary' => [
                'output_tax_total' => $this->money($outputTaxTotal),
                'input_tax_total' => $this->money($inputTaxTotal),
                'expense_tax_total' => $this->money($expenseTaxTotal),
                'tax_difference' => $this->money($taxDifference),
                'input_payment_summary' => $this->paymentMethodSummary($inputRows),
                'output_payment_summary' => $this->paymentMethodSummary($outputRows),
                'expense_payment_summary' => $this->paymentMethodSummary($expenseRows),
                'input_total_before_tax' => $this->money($this->sumRawColumn($inputRows)),
                'output_total_before_tax' => $this->money($this->sumRawColumn($outputRows)),
                'expense_total_before_tax' => $this->money($this->sumRawColumn($expenseRows)),
            ],
            'input_rows' => $inputRows,
            'output_rows' => $outputRows,
            'expense_rows' => $expenseRows,
            'input_column_totals' => $inputColumnTotals,
            'output_column_totals' => $outputColumnTotals,
            'expense_column_totals' => $expenseColumnTotals,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function sumRawColumn(array $rows): float
    {
        $s = 0.0;
        foreach ($rows as $row) {
            $s += (float) ($row['total_before_tax_raw'] ?? 0);
        }

        return round($s, 4);
    }

    /**
     * @param  Collection<int, TaxRate>  $rates
     * @param  list<int>  $rateIds
     * @return list<array<string, mixed>>
     */
    protected function buildPurchaseTaxRows(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        Collection $rates,
        array $rateIds,
    ): array {
        $q = Purchase::query()
            ->forTeam($team)
            ->where('status', 'received')
            ->whereBetween('transaction_date', [$start, $end])
            ->with(['supplier', 'lines', 'payments', 'taxRate'])
            ->orderByDesc('transaction_date');

        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }

        $rows = [];
        foreach ($q->get() as $purchase) {
            $taxes = $this->emptyTaxMap($rateIds);
            foreach ($purchase->lines as $line) {
                $rid = $this->rateIdForLinePercent($rates, (float) $line->product_tax_percent);
                if ($rid !== null) {
                    $taxes[$rid] = round($taxes[$rid] + (float) $line->line_tax_amount, 4);
                }
            }
            if ((float) $purchase->purchase_tax_amount > 0 && $purchase->tax_rate_id) {
                $rid = (int) $purchase->tax_rate_id;
                if (isset($taxes[$rid])) {
                    $taxes[$rid] = round($taxes[$rid] + (float) $purchase->purchase_tax_amount, 4);
                }
            }

            $linesTotal = (float) $purchase->lines_total;
            $discount = $this->headerDiscountValue(
                $linesTotal,
                (string) $purchase->discount_type,
                (float) $purchase->discount_amount,
                isPurchase: true,
            );

            $beforeRaw = round((float) $purchase->lines->sum('line_subtotal_exc_tax'), 4);

            $rows[] = [
                'date' => $purchase->transaction_date?->toIso8601String(),
                'reference' => $purchase->ref_no ?? '—',
                'party' => $purchase->supplier?->display_name ?? '—',
                'tax_number' => $purchase->supplier?->tax_number ? (string) $purchase->supplier->tax_number : '—',
                'total_before_tax_raw' => $beforeRaw,
                'total_before_tax' => $this->money($beforeRaw),
                'payment_method' => $this->formatPaymentMethods($purchase->payments),
                'discount' => $this->money($discount),
                'taxes' => $this->taxesToStrings($taxes),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, TaxRate>  $rates
     * @param  list<int>  $rateIds
     * @return list<array<string, mixed>>
     */
    protected function buildSaleTaxRows(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $customerId,
        Collection $rates,
        array $rateIds,
    ): array {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end])
            ->with(['customer', 'lines', 'payments', 'taxRate'])
            ->orderByDesc('transaction_date');

        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($customerId) {
            $q->where('customer_id', $customerId);
        }

        $rows = [];
        foreach ($q->get() as $sale) {
            $taxes = $this->emptyTaxMap($rateIds);
            foreach ($sale->lines as $line) {
                $rid = $this->rateIdForLinePercent($rates, (float) $line->product_tax_percent);
                if ($rid !== null) {
                    $taxes[$rid] = round($taxes[$rid] + (float) $line->line_tax_amount, 4);
                }
            }
            if ((float) $sale->sale_tax_amount > 0 && $sale->tax_rate_id) {
                $rid = (int) $sale->tax_rate_id;
                if (isset($taxes[$rid])) {
                    $taxes[$rid] = round($taxes[$rid] + (float) $sale->sale_tax_amount, 4);
                }
            }

            $linesTotal = (float) $sale->lines_total;
            $discount = $this->headerDiscountValue(
                $linesTotal,
                (string) $sale->discount_type,
                (float) $sale->discount_amount,
                isPurchase: false,
            );

            $beforeRaw = round((float) $sale->lines->sum('line_subtotal_exc_tax'), 4);

            $rows[] = [
                'date' => $sale->transaction_date?->toIso8601String(),
                'reference' => $sale->invoice_no ?? '—',
                'party' => $sale->customer?->display_name ?? '—',
                'tax_number' => $sale->customer?->tax_number ? (string) $sale->customer->tax_number : '—',
                'total_before_tax_raw' => $beforeRaw,
                'total_before_tax' => $this->money($beforeRaw),
                'payment_method' => $this->formatPaymentMethods($sale->payments),
                'discount' => $this->money($discount),
                'taxes' => $this->taxesToStrings($taxes),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, TaxRate>  $rates
     * @param  list<int>  $rateIds
     * @return list<array<string, mixed>>
     */
    protected function buildExpenseTaxRows(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $customerId,
        Collection $rates,
        array $rateIds,
    ): array {
        $q = Expense::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end])
            ->with(['payments', 'taxRate', 'contact'])
            ->orderByDesc('transaction_date');

        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($customerId) {
            $q->where('contact_id', $customerId);
        }

        $rows = [];
        foreach ($q->get() as $expense) {
            $sign = $expense->is_refund ? -1.0 : 1.0;
            $taxAmount = round((float) $expense->tax_amount * $sign, 4);
            $beforeRaw = round(((float) $expense->final_total - (float) $expense->tax_amount) * $sign, 4);

            $taxes = $this->emptyTaxMap($rateIds);
            if ($taxAmount !== 0.0 && $expense->tax_rate_id) {
                $rid = (int) $expense->tax_rate_id;
                if (isset($taxes[$rid])) {
                    $taxes[$rid] = round($taxes[$rid] + $taxAmount, 4);
                }
            }

            $rows[] = [
                'date' => $expense->transaction_date?->toIso8601String(),
                'reference' => $expense->ref_no ?? '—',
                'party' => $expense->contact?->display_name ?? '—',
                'tax_number' => $expense->contact?->tax_number ? (string) $expense->contact->tax_number : '—',
                'total_before_tax_raw' => $beforeRaw,
                'total_before_tax' => $this->money($beforeRaw),
                'payment_method' => $this->formatPaymentMethods($expense->payments),
                'discount' => $this->money(0.0),
                'taxes' => $this->taxesToStrings($taxes),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<int>  $rateIds
     * @return array<int, float>
     */
    protected function emptyTaxMap(array $rateIds): array
    {
        $m = [];
        foreach ($rateIds as $id) {
            $m[$id] = 0.0;
        }

        return $m;
    }

    /**
     * @param  Collection<int, TaxRate>  $rates
     */
    protected function rateIdForLinePercent(Collection $rates, float $percent): ?int
    {
        if ($percent <= 0) {
            return null;
        }
        foreach ($rates as $rate) {
            if (abs((float) $rate->amount - $percent) < 0.00011) {
                return (int) $rate->id;
            }
        }

        return null;
    }

    /**
     * @param  array<int, float>  $taxes
     * @return array<string, string>
     */
    protected function taxesToStrings(array $taxes): array
    {
        $out = [];
        foreach ($taxes as $id => $v) {
            $out['tax_'.$id] = $this->money($v);
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<int>  $rateIds
     * @return array<string, string>
     */
    protected function sumTaxColumns(array $rows, array $rateIds): array
    {
        $sums = $this->emptyTaxMap($rateIds);
        foreach ($rows as $row) {
            $taxes = $row['taxes'] ?? [];
            foreach ($rateIds as $id) {
                $key = 'tax_'.$id;
                $sums[$id] = round($sums[$id] + floatval($taxes[$key] ?? 0), 4);
            }
        }
        $out = [];
        foreach ($sums as $id => $v) {
            $out['tax_'.$id] = $this->money($v);
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function paymentMethodSummary(array $rows): string
    {
        $counts = [];
        foreach ($rows as $row) {
            $m = trim((string) ($row['payment_method'] ?? ''));
            if ($m === '' || $m === '—') {
                continue;
            }
            $counts[$m] = ($counts[$m] ?? 0) + 1;
        }
        if ($counts === []) {
            return '';
        }
        ksort($counts);
        $parts = [];
        foreach ($counts as $method => $n) {
            $parts[] = $method.' — '.$n;
        }

        return implode("\n", $parts);
    }

    protected function formatPaymentMethods(Collection $payments): string
    {
        if ($payments->isEmpty()) {
            return '—';
        }

        return $payments->pluck('method')->unique()->implode(', ');
    }

    protected function headerDiscountValue(float $linesTotal, string $discountType, float $discountAmount, bool $isPurchase): float
    {
        if ($discountType === 'none') {
            return 0.0;
        }
        $svc = $isPurchase ? $this->purchaseService : $this->saleService;
        $after = $svc->applyHeaderDiscount($linesTotal, $discountType, $discountAmount);

        return round($linesTotal - $after, 4);
    }

    protected function money(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
