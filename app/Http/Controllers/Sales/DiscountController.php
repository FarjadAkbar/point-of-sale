<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreDiscountRequest;
use App\Models\Brand;
use App\Models\BusinessLocation;
use App\Models\Discount;
use App\Models\ProductCategory;
use App\Models\SellingPriceGroup;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    public function index(Team $current_team): Response
    {
        $discounts = Discount::query()
            ->forTeam($current_team)
            ->with([
                'businessLocation',
                'brand',
                'productCategory',
                'sellingPriceGroup',
                'products',
            ])
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Discount $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'priority' => $d->priority,
                'discount_type' => $d->discount_type,
                'discount_amount' => (string) $d->discount_amount,
                'starts_at' => $d->starts_at?->toIso8601String(),
                'ends_at' => $d->ends_at?->toIso8601String(),
                'is_active' => $d->is_active,
                'applicable_in_customer_groups' => $d->applicable_in_customer_groups,
                'business_location' => $d->businessLocation ? [
                    'id' => $d->businessLocation->id,
                    'name' => $d->businessLocation->name,
                ] : null,
                'brand' => $d->brand ? ['id' => $d->brand->id, 'name' => $d->brand->name] : null,
                'category' => $d->productCategory ? [
                    'id' => $d->productCategory->id,
                    'name' => $d->productCategory->name,
                ] : null,
                'selling_price_group' => $d->sellingPriceGroup ? [
                    'id' => $d->sellingPriceGroup->id,
                    'name' => $d->sellingPriceGroup->name,
                ] : null,
                'products_count' => $d->products->count(),
            ]);

        return Inertia::render('sales/discounts/Index', [
            'discounts' => $discounts,
            'brands' => Brand::query()
                ->forTeam($current_team)
                ->orderBy('name')
                ->get(['id', 'name']),
            'productCategories' => ProductCategory::query()
                ->forTeam($current_team)
                ->orderBy('name')
                ->get(['id', 'name']),
            'businessLocations' => BusinessLocation::query()
                ->forTeam($current_team)
                ->orderBy('name')
                ->get(['id', 'name']),
            'sellingPriceGroups' => SellingPriceGroup::query()
                ->forTeam($current_team)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreDiscountRequest $request, Team $current_team): RedirectResponse
    {
        $payload = $request->discountPayload();
        $productIds = $payload['product_ids'];
        unset($payload['product_ids']);

        DB::transaction(function () use ($current_team, $payload, $productIds) {
            $discount = Discount::query()->create([
                ...$payload,
                'team_id' => $current_team->id,
            ]);

            if ($productIds !== []) {
                $discount->products()->sync($productIds);
            }
        });

        return back()->with('success', 'Discount saved.');
    }
}
