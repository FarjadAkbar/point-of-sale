<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function index(Team $current_team): Response
    {
        $query = Sale::query()
            ->forTeam($current_team)
            ->where('status', 'final')
            ->with(['customer', 'businessLocation'])
            ->orderByDesc('transaction_date');

        $paginator = $query->paginate(20)->withQueryString();

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
        ]);
    }
}
