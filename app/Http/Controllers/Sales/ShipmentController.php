<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\ShipmentIndexRequest;
use App\Models\Sale;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function index(ShipmentIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $query = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'final')
            ->with(['customer', 'businessLocation']);

        if (! empty($filters['search'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_no', 'like', $term)
                    ->orWhere('shipping_status', 'like', $term)
                    ->orWhere('delivered_to', 'like', $term)
                    ->orWhere('delivery_person', 'like', $term)
                    ->orWhereHas('customer', function ($c) use ($term) {
                        $c->where('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('customer_code', 'like', $term);
                    })
                    ->orWhereHas('businessLocation', fn ($l) => $l->where('name', 'like', $term));
            });
        }

        $sort = $filters['sort'] ?? 'transaction_date';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'transaction_date', 'final_total', 'shipping_status', 'shipping_charges', 'invoice_no'];
        $query->orderBy(
            in_array($sort, $allowedSort, true) ? $sort : 'transaction_date',
            $direction
        );

        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 15)));
        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->through(function (Sale $sale) {
            return [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'transaction_date' => $sale->transaction_date?->toIso8601String(),
                'final_total' => (string) $sale->final_total,
                'shipping_details' => $sale->shipping_details,
                'shipping_charges' => (string) $sale->shipping_charges,
                'shipping_address' => $sale->shipping_address,
                'shipping_status' => $sale->shipping_status,
                'delivered_to' => $sale->delivered_to,
                'delivery_person' => $sale->delivery_person,
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'display_name' => $sale->customer->display_name,
                ] : null,
                'business_location' => $sale->businessLocation ? [
                    'id' => $sale->businessLocation->id,
                    'name' => $sale->businessLocation->name,
                ] : null,
            ];
        });

        return Inertia::render('sales/shipments/Index', [
            'shipments' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'transaction_date',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
        ]);
    }
}
