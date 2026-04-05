<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseReturnController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        $perPage = min(100, max(10, (int) $request->query('per_page', 15)));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('purchase-returns/Index', [
            'purchaseReturns' => $paginator,
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'sort' => (string) $request->query('sort', 'created_at'),
                'direction' => (string) $request->query('direction', 'desc'),
                'per_page' => $perPage,
            ],
        ]);
    }
}
