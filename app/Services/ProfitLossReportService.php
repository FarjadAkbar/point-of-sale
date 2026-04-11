<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService
{
    public function __construct(
        protected SaleService $saleService,
        protected PurchaseService $purchaseService,
    ) {}

    /**
     * @return array{
     *     summary: array<string, string>,
     *     profit_by_products: list<array{name: string, gross_profit: string}>,
     *     profit_by_categories: list<array{name: string, gross_profit: string}>,
     *     profit_by_brands: list<array{name: string, gross_profit: string}>,
     *     profit_by_locations: list<array{name: string, gross_profit: string}>,
     *     profit_by_invoice: list<array{invoice: string, gross_profit: string}>,
     *     profit_by_date: list<array{date: string, gross_profit: string}>,
     *     profit_by_customer: list<array{name: string, gross_profit: string}>,
     *     profit_by_day: list<array{day: string, gross_profit: string}>,
     *     profit_by_service_staff: list<array{name: string, gross_profit: string}>,
     * }
     */
    public function build(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $lineBase = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId);

        $totalSalesExcTax = (clone $lineBase)->sum('sale_lines.line_subtotal_exc_tax');
        $cogsDefaultCost = (clone $lineBase)->sum(
            DB::raw('sale_lines.quantity * COALESCE(products.single_dpp, 0)')
        );
        $grossProfit = (float) $totalSalesExcTax - (float) $cogsDefaultCost;

        $totalSellLineDiscount = (clone $lineBase)->sum(
            DB::raw('sale_lines.quantity * (sale_lines.unit_price_before_discount - sale_lines.unit_price_exc_tax)')
        );

        $saleScope = $this->salesInRange($team, $start, $end, $businessLocationId);
        $saleShipping = (clone $saleScope)->sum('shipping_charges');
        $saleAdditional = $this->sumJsonAdditionalExpenses(
            (clone $saleScope)->whereNotNull('additional_expenses')->get(['additional_expenses'])
        );
        $totalSaleHeaderDiscount = (clone $saleScope)->get(['lines_total', 'discount_type', 'discount_amount'])
            ->sum(function (Sale $sale) {
                if (($sale->discount_type ?? 'none') === 'none') {
                    return 0.0;
                }

                $linesTotal = (float) $sale->lines_total;
                $after = $this->saleService->applyHeaderDiscount(
                    $linesTotal,
                    (string) $sale->discount_type,
                    (float) $sale->discount_amount,
                );

                return round($linesTotal - $after, 4);
            });

        $purchaseScope = $this->purchasesInRange($team, $start, $end, $businessLocationId);
        $totalPurchaseExcTax = (clone $purchaseScope)
            ->join('purchase_lines', 'purchases.id', '=', 'purchase_lines.purchase_id')
            ->sum('purchase_lines.line_subtotal_exc_tax');
        $purchaseShipping = (clone $purchaseScope)->sum('shipping_charges');
        $purchaseAdditional = $this->sumJsonAdditionalExpenses(
            (clone $purchaseScope)->whereNotNull('additional_expenses')->get(['additional_expenses'])
        );
        $totalPurchaseHeaderDiscount = (clone $purchaseScope)->get(['lines_total', 'discount_type', 'discount_amount'])
            ->sum(function (Purchase $purchase) {
                if (($purchase->discount_type ?? 'none') === 'none') {
                    return 0.0;
                }

                $linesTotal = (float) $purchase->lines_total;
                $after = $this->purchaseService->applyHeaderDiscount(
                    $linesTotal,
                    (string) $purchase->discount_type,
                    (float) $purchase->discount_amount,
                );

                return round($linesTotal - $after, 4);
            });

        $expenseScope = Expense::query()->forTeam($team)->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $expenseScope->where('business_location_id', $businessLocationId);
        }
        $totalExpense = (clone $expenseScope)->get(['final_total', 'is_refund'])->sum(function (Expense $e) {
            $v = (float) $e->final_total;

            return $e->is_refund ? -$v : $v;
        });

        $adjScope = StockAdjustment::query()->forTeam($team)->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $adjScope->where('business_location_id', $businessLocationId);
        }
        $totalStockAdjustment = (clone $adjScope)->sum('final_total');
        $totalStockRecovered = (clone $adjScope)->sum('total_amount_recovered');

        $transferScope = StockTransfer::query()->forTeam($team)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $transferScope->where(function ($q) use ($businessLocationId) {
                $q->where('from_business_location_id', $businessLocationId)
                    ->orWhere('to_business_location_id', $businessLocationId);
            });
        }
        $totalTransferShipping = (clone $transferScope)->sum('shipping_charges');

        $returnScope = SaleReturn::query()->forTeam($team)->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $returnScope->whereHas('parentSale', fn (Builder $q) => $q->where('business_location_id', $businessLocationId));
        }
        $totalSellReturn = (clone $returnScope)->sum('total_return');

        [$closingStockPurchase, $closingStockSale] = $this->closingStockValuation($team, $businessLocationId);

        $openingPurchase = 0.0;
        $openingSale = 0.0;
        $cogsClassic = $openingPurchase + (float) $totalPurchaseExcTax - (float) $closingStockPurchase;

        $netProfit = $grossProfit
            + (float) $saleShipping
            + $saleAdditional
            + (float) $totalStockRecovered
            + (float) $totalPurchaseHeaderDiscount
            - (float) $totalExpense
            - (float) $purchaseShipping
            - $purchaseAdditional
            - (float) $totalTransferShipping
            - (float) $totalStockAdjustment
            - (float) $totalSellReturn
            - (float) $totalSaleHeaderDiscount;

        $summary = [
            'opening_stock_purchase' => $this->money($openingPurchase),
            'opening_stock_sale' => $this->money($openingSale),
            'total_purchase_exc_tax' => $this->money((float) $totalPurchaseExcTax),
            'total_stock_adjustment' => $this->money((float) $totalStockAdjustment),
            'total_expense' => $this->money((float) $totalExpense),
            'total_purchase_shipping' => $this->money((float) $purchaseShipping),
            'purchase_additional_expenses' => $this->money($purchaseAdditional),
            'total_transfer_shipping' => $this->money((float) $totalTransferShipping),
            'total_sell_discount' => $this->money((float) $totalSellLineDiscount),
            'total_customer_reward' => $this->money(0),
            'total_sell_return' => $this->money((float) $totalSellReturn),
            'total_payroll' => $this->money(0),
            'total_production_cost' => $this->money(0),
            'closing_stock_purchase' => $this->money((float) $closingStockPurchase),
            'closing_stock_sale' => $this->money((float) $closingStockSale),
            'total_sales_exc_tax' => $this->money((float) $totalSalesExcTax),
            'total_sell_shipping' => $this->money((float) $saleShipping),
            'sell_additional_expenses' => $this->money($saleAdditional),
            'total_stock_recovered' => $this->money((float) $totalStockRecovered),
            'total_purchase_return' => $this->money(0),
            'total_purchase_discount' => $this->money((float) $totalPurchaseHeaderDiscount),
            'total_sell_round_off' => $this->money(0),
            'hms_total' => $this->money(0),
            'cogs' => $this->money($cogsClassic),
            'cogs_sold_goods' => $this->money((float) $cogsDefaultCost),
            'gross_profit' => $this->money($grossProfit),
            'net_profit' => $this->money($netProfit),
            'total_sale_header_discount' => $this->money((float) $totalSaleHeaderDiscount),
        ];

        return [
            'summary' => $summary,
            'profit_by_products' => $this->profitByProducts($team, $start, $end, $businessLocationId),
            'profit_by_categories' => $this->profitByCategories($team, $start, $end, $businessLocationId),
            'profit_by_brands' => $this->profitByBrands($team, $start, $end, $businessLocationId),
            'profit_by_locations' => $this->profitByLocations($team, $start, $end, $businessLocationId),
            'profit_by_invoice' => $this->profitByInvoice($team, $start, $end, $businessLocationId),
            'profit_by_date' => $this->profitByDate($team, $start, $end, $businessLocationId),
            'profit_by_customer' => $this->profitByCustomer($team, $start, $end, $businessLocationId),
            'profit_by_day' => $this->profitByDayOfWeek($team, $start, $end, $businessLocationId),
            'profit_by_service_staff' => [],
        ];
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function saleLinesJoinedQuery(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId)
    {
        return DB::table('sale_lines')
            ->join('sales', 'sale_lines.sale_id', '=', 'sales.id')
            ->join('products', 'sale_lines.product_id', '=', 'products.id')
            ->where('sales.team_id', $team->id)
            ->where('sales.status', 'final')
            ->whereBetween('sales.transaction_date', [$start, $end])
            ->when($businessLocationId, fn ($q) => $q->where('sales.business_location_id', $businessLocationId));
    }

    protected function salesInRange(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): Builder
    {
        $q = Sale::query()
            ->forTeam($team)
            ->where('status', 'final')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }

        return $q;
    }

    protected function purchasesInRange(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): Builder
    {
        $q = Purchase::query()
            ->forTeam($team)
            ->where('status', 'received')
            ->whereBetween('transaction_date', [$start, $end]);
        if ($businessLocationId) {
            $q->where('business_location_id', $businessLocationId);
        }

        return $q;
    }

    /**
     * @param  Collection<int, Sale|Purchase>  $models
     */
    protected function sumJsonAdditionalExpenses($models): float
    {
        $sum = 0.0;
        foreach ($models as $model) {
            $rows = $model->additional_expenses;
            if (! is_array($rows)) {
                continue;
            }
            $sum += $this->saleService->sumAdditionalExpenses($rows);
        }

        return round($sum, 4);
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function closingStockValuation(Team $team, ?int $businessLocationId): array
    {
        $q = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->where('products.team_id', $team->id)
            ->where('products.manage_stock', true)
            ->when($businessLocationId, fn ($query) => $query->where(
                'product_stocks.business_location_id',
                $businessLocationId
            ));

        $purchase = (float) (clone $q)->sum(DB::raw('product_stocks.quantity * COALESCE(products.single_dpp, 0)'));
        $sale = (float) (clone $q)->sum(DB::raw('product_stocks.quantity * COALESCE(products.single_dsp, 0)'));

        return [round($purchase, 4), round($sale, 4)];
    }

    /**
     * @return list<array{name: string, gross_profit: string}>
     */
    protected function profitByProducts(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->selectRaw(
                'products.name as name, products.sku as sku, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(function ($row) {
            $label = trim((string) $row->name);
            $sku = trim((string) $row->sku);
            if ($sku !== '') {
                $label .= ' ('.$sku.')';
            }

            return [
                'name' => $label,
                'gross_profit' => $this->money((float) $row->gross),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{name: string, gross_profit: string}>
     */
    protected function profitByCategories(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->groupBy('product_categories.id', 'product_categories.name')
            ->selectRaw(
                'COALESCE(NULLIF(TRIM(product_categories.name), ""), "Uncategorized") as cat_name, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(fn ($row) => [
            'name' => (string) $row->cat_name,
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{name: string, gross_profit: string}>
     */
    protected function profitByBrands(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->groupBy('brands.id', 'brands.name')
            ->selectRaw(
                'COALESCE(NULLIF(TRIM(brands.name), ""), "Others") as brand_name, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(fn ($row) => [
            'name' => (string) $row->brand_name,
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{name: string, gross_profit: string}>
     */
    protected function profitByLocations(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->join('business_locations', 'sales.business_location_id', '=', 'business_locations.id')
            ->groupBy('business_locations.id', 'business_locations.name')
            ->selectRaw(
                'business_locations.name as loc_name, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(fn ($row) => [
            'name' => (string) $row->loc_name,
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{invoice: string, gross_profit: string}>
     */
    protected function profitByInvoice(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $driver = DB::connection()->getDriverName();
        $invoiceLabel = $driver === 'sqlite'
            ? 'COALESCE(NULLIF(TRIM(sales.invoice_no), ""), printf("#%d", sales.id))'
            : 'COALESCE(NULLIF(TRIM(sales.invoice_no), ""), CONCAT("#", sales.id))';

        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->groupBy('sales.id', 'sales.invoice_no')
            ->selectRaw(
                $invoiceLabel.' as inv, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(fn ($row) => [
            'invoice' => (string) $row->inv,
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{date: string, gross_profit: string}>
     */
    protected function profitByDate(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', sales.transaction_date)"
            : 'DATE(sales.transaction_date)';

        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->groupBy(DB::raw($dateExpr))
            ->selectRaw(
                $dateExpr.' as d, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderBy('d')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => (string) $row->d,
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{name: string, gross_profit: string}>
     */
    protected function profitByCustomer(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $driver = DB::connection()->getDriverName();
        $custExpr = $driver === 'sqlite'
            ? 'TRIM(COALESCE(NULLIF(TRIM(customers.business_name), ""), TRIM(COALESCE(customers.first_name, "") || " " || COALESCE(customers.last_name, ""))))'
            : 'TRIM(COALESCE(NULLIF(TRIM(customers.business_name), ""), CONCAT_WS(" ", customers.first_name, customers.last_name)))';

        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->groupBy('customers.id', 'customers.business_name', 'customers.first_name', 'customers.last_name')
            ->selectRaw(
                $custExpr.' as cust_name, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderByDesc('gross')
            ->get();

        return $rows->map(fn ($row) => [
            'name' => (string) $row->cust_name !== '' ? (string) $row->cust_name : '—',
            'gross_profit' => $this->money((float) $row->gross),
        ])->values()->all();
    }

    /**
     * @return list<array{day: string, gross_profit: string}>
     */
    protected function profitByDayOfWeek(Team $team, Carbon $start, Carbon $end, ?int $businessLocationId): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $dowExpr = "cast(strftime('%w', sales.transaction_date) as integer)";
        } else {
            $dowExpr = 'weekday(sales.transaction_date)';
        }

        $rows = $this->saleLinesJoinedQuery($team, $start, $end, $businessLocationId)
            ->groupBy(DB::raw($dowExpr))
            ->selectRaw(
                $dowExpr.' as dow, SUM(sale_lines.line_subtotal_exc_tax - sale_lines.quantity * COALESCE(products.single_dpp, 0)) as gross'
            )
            ->orderBy('dow')
            ->get();

        $namesMysql = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $namesSqlite = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return $rows->map(function ($row) use ($driver, $namesMysql, $namesSqlite) {
            $dow = (int) $row->dow;
            $day = $driver === 'sqlite'
                ? ($namesSqlite[$dow] ?? (string) $dow)
                : ($namesMysql[$dow] ?? (string) $dow);

            return [
                'day' => $day,
                'gross_profit' => $this->money((float) $row->gross),
            ];
        })->values()->all();
    }

    protected function money(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
