<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\Team;
use Carbon\Carbon;

class CustomerSuppliersReportService
{
    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     footer: array<string, string>,
     * }
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        string $contactType,
        ?int $customerGroupId,
        ?string $contactKey,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $contactTypeNorm = in_array($contactType, ['', 'all'], true) ? '' : $contactType;

        $customerContactId = null;
        $supplierContactId = null;
        if ($contactKey !== null && $contactKey !== '') {
            if (preg_match('/^c-(\d+)$/', $contactKey, $m)) {
                $customerContactId = (int) $m[1];
            } elseif (preg_match('/^s-(\d+)$/', $contactKey, $m)) {
                $supplierContactId = (int) $m[1];
            }
        }

        $purchaseBuckets = $this->aggregatePurchases($team, $start, $end, $businessLocationId, $supplierContactId);
        $saleBuckets = $this->aggregateSales($team, $start, $end, $businessLocationId, $customerContactId);
        $returnByCustomer = $this->aggregateSaleReturns($team, $start, $end, $businessLocationId, $customerContactId);

        $rows = [];
        $includeCustomers = $contactTypeNorm === '' || $contactTypeNorm === 'customer';
        $includeSuppliers = $contactTypeNorm === '' || $contactTypeNorm === 'supplier';

        if ($includeCustomers) {
            $cq = Customer::query()
                ->forTeam($team)
                ->whereIn('party_role', ['customer', 'both'])
                ->orderBy('business_name')
                ->orderBy('first_name');
            if ($customerGroupId) {
                $cq->where('customer_group_id', $customerGroupId);
            }
            if ($customerContactId) {
                $cq->whereKey($customerContactId);
            }
            foreach ($cq->get() as $customer) {
                $cid = $customer->id;
                $saleTotal = $saleBuckets['totals'][$cid] ?? 0.0;
                $saleUnpaid = $saleBuckets['unpaid'][$cid] ?? 0.0;
                $returnTotal = $returnByCustomer[$cid] ?? 0.0;
                $opening = (float) ($customer->opening_balance ?? 0);
                $due = round($opening + $saleUnpaid - $returnTotal, 4);

                $rows[] = [
                    'kind' => 'customer',
                    'id' => $cid,
                    'name' => $this->formatCustomerName($customer),
                    'url' => route('customers.index', ['current_team' => $team->slug]).'?'.http_build_query([
                        'search' => $customer->customer_code ?? $customer->display_name,
                    ]),
                    'total_purchase' => $this->money(0.0),
                    'total_purchase_return' => $this->money(0.0),
                    'total_sale' => $this->money($saleTotal),
                    'total_sell_return' => $this->money($returnTotal),
                    'opening_balance_due' => $this->money($opening),
                    'due' => $this->money($due),
                ];
            }
        }

        if ($includeSuppliers) {
            $sq = Supplier::query()->forTeam($team)->orderBy('business_name')->orderBy('first_name');
            if ($supplierContactId) {
                $sq->whereKey($supplierContactId);
            }
            foreach ($sq->get() as $supplier) {
                $sid = $supplier->id;
                $purchaseTotal = $purchaseBuckets['totals'][$sid] ?? 0.0;
                $purchaseUnpaid = $purchaseBuckets['unpaid'][$sid] ?? 0.0;
                $opening = (float) ($supplier->opening_balance ?? 0);
                $due = round(-$opening - $purchaseUnpaid, 4);

                $rows[] = [
                    'kind' => 'supplier',
                    'id' => $sid,
                    'name' => $this->formatSupplierName($supplier),
                    'url' => route('suppliers.index', ['current_team' => $team->slug]).'?'.http_build_query([
                        'search' => $supplier->supplier_code ?? $supplier->display_name,
                    ]),
                    'total_purchase' => $this->money($purchaseTotal),
                    'total_purchase_return' => $this->money(0.0),
                    'total_sale' => $this->money(0.0),
                    'total_sell_return' => $this->money(0.0),
                    'opening_balance_due' => $this->money($opening),
                    'due' => $this->money($due),
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));

        $footer = $this->sumFooter($rows);

        return [
            'rows' => $rows,
            'footer' => $footer,
        ];
    }

    /**
     * @return array{totals: array<int, float>, unpaid: array<int, float>}
     */
    protected function aggregatePurchases(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $onlySupplierId,
    ): array {
        $q = Purchase::query()
            ->forTeam($team)
            ->where('status', 'received')
            ->whereBetween('transaction_date', [$start, $end])
            ->withSum('payments', 'amount');
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($onlySupplierId) {
            $q->where('supplier_id', $onlySupplierId);
        }

        $totals = [];
        $unpaid = [];
        foreach ($q->get() as $purchase) {
            $sid = (int) $purchase->supplier_id;
            $ft = (float) $purchase->final_total;
            $paid = (float) ($purchase->payments_sum_amount ?? 0);
            $totals[$sid] = round(($totals[$sid] ?? 0) + $ft, 4);
            $unpaid[$sid] = round(($unpaid[$sid] ?? 0) + max(0.0, $ft - $paid), 4);
        }

        return ['totals' => $totals, 'unpaid' => $unpaid];
    }

    /**
     * @return array{totals: array<int, float>, unpaid: array<int, float>}
     */
    protected function aggregateSales(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $onlyCustomerId,
    ): array {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end])
            ->withSum('payments', 'amount');
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }
        if ($onlyCustomerId) {
            $q->where('customer_id', $onlyCustomerId);
        }

        $totals = [];
        $unpaid = [];
        foreach ($q->get() as $sale) {
            $cid = (int) $sale->customer_id;
            $ft = (float) $sale->final_total;
            $paid = (float) ($sale->payments_sum_amount ?? 0);
            $totals[$cid] = round(($totals[$cid] ?? 0) + $ft, 4);
            $unpaid[$cid] = round(($unpaid[$cid] ?? 0) + max(0.0, $ft - $paid), 4);
        }

        return ['totals' => $totals, 'unpaid' => $unpaid];
    }

    /**
     * @return array<int, float>
     */
    protected function aggregateSaleReturns(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $onlyCustomerId,
    ): array {
        $q = SaleReturn::query()
            ->forTeam($team)
            ->whereBetween('transaction_date', [$start, $end])
            ->whereHas('parentSale', function ($q) use ($team, $businessLocationId, $onlyCustomerId) {
                $q->forTeam($team)->where('status', 'final');
                if ($businessLocationId) {
                    $q->where('business_location_id', $businessLocationId);
                }
                if ($onlyCustomerId) {
                    $q->where('customer_id', $onlyCustomerId);
                }
            })
            ->with('parentSale');

        $byCustomer = [];
        foreach ($q->get() as $ret) {
            $cid = (int) ($ret->parentSale?->customer_id ?? 0);
            if ($cid === 0) {
                continue;
            }
            $byCustomer[$cid] = round(($byCustomer[$cid] ?? 0) + (float) $ret->total_return, 4);
        }

        return $byCustomer;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    protected function sumFooter(array $rows): array
    {
        $tp = $tpr = $ts = $tsr = $ob = $due = 0.0;
        foreach ($rows as $r) {
            $tp += floatval((string) ($r['total_purchase'] ?? 0));
            $tpr += floatval((string) ($r['total_purchase_return'] ?? 0));
            $ts += floatval((string) ($r['total_sale'] ?? 0));
            $tsr += floatval((string) ($r['total_sell_return'] ?? 0));
            $ob += floatval((string) ($r['opening_balance_due'] ?? 0));
            $due += floatval((string) ($r['due'] ?? 0));
        }

        return [
            'total_purchase' => $this->money($tp),
            'total_purchase_return' => $this->money($tpr),
            'total_sale' => $this->money($ts),
            'total_sell_return' => $this->money($tsr),
            'opening_balance_due' => $this->money($ob),
            'due' => $this->money($due),
        ];
    }

    protected function formatCustomerName(Customer $c): string
    {
        $name = $c->display_name;
        if (filled($c->customer_code)) {
            $name .= ' ('.$c->customer_code.')';
        }

        return $name;
    }

    protected function formatSupplierName(Supplier $s): string
    {
        $parts = array_filter([
            trim(implode(' ', array_filter([$s->first_name, $s->last_name]))),
            $s->business_name,
        ]);

        $name = $parts !== [] ? implode(', ', $parts) : $s->display_name;
        if (filled($s->supplier_code)) {
            $name .= ' ('.$s->supplier_code.')';
        }

        return $name;
    }

    protected function money(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
