<?php

namespace App\Services;

use App\Models\SaleLine;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrendingProductsReportService
{
    /**
     * @return list<array{product_id: int, label: string, units_sold: string, line_total: string}>
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $businessLocationId,
        ?int $categoryId,
        ?int $subcategoryId,
        ?int $brandId,
        ?int $unitId,
        string $productType,
        int $limit,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $pt = $productType === '' ? null : $productType;

        $q = SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sales.team_id', $team->id)
            ->where('products.team_id', $team->id)
            ->where('sales.status', 'final')
            ->whereBetween('sales.transaction_date', [$start, $end]);

        if ($businessLocationId) {
            $q->where('sales.business_location_id', $businessLocationId);
        }
        if ($categoryId) {
            $q->where('products.category_id', $categoryId);
        }
        if ($subcategoryId) {
            $q->where('products.subcategory_id', $subcategoryId);
        }
        if ($brandId) {
            $q->where('products.brand_id', $brandId);
        }
        if ($unitId) {
            $q->where('products.unit_id', $unitId);
        }
        if ($pt) {
            $q->where('products.product_type', $pt);
        }

        $agg = $q
            ->select([
                'products.id as product_id',
                DB::raw('MAX(products.name) as name'),
                DB::raw('MAX(products.sku) as sku'),
                DB::raw('SUM(sale_lines.quantity) as units_sold'),
                DB::raw('SUM(sale_lines.line_total) as line_total'),
            ])
            ->groupBy('products.id')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($agg as $r) {
            $out[] = [
                'product_id' => (int) $r->product_id,
                'label' => trim($r->name.' — '.($r->sku ?? '')),
                'units_sold' => number_format((float) $r->units_sold, 4, '.', ''),
                'line_total' => number_format((float) $r->line_total, 4, '.', ''),
            ];
        }

        return $out;
    }
}
