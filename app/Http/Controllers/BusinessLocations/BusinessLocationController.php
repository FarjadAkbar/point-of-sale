<?php

namespace App\Http\Controllers\BusinessLocations;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessLocations\BusinessLocationIndexRequest;
use App\Http\Requests\BusinessLocations\StoreBusinessLocationRequest;
use App\Http\Requests\BusinessLocations\UpdateBusinessLocationRequest;
use App\Http\Resources\BusinessLocationResource;
use App\Models\BusinessLocation;
use App\Models\Product;
use App\Models\SellingPriceGroup;
use App\Models\Team;
use App\Services\BusinessLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessLocationController extends Controller
{
    public function __construct(
        protected BusinessLocationService $businessLocationService,
    ) {}

    public function index(BusinessLocationIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->businessLocationService->paginate($current_team, $filters);
        $paginator->through(fn (BusinessLocation $loc) => (new BusinessLocationResource($loc))->resolve());

        $sellingPriceGroups = SellingPriceGroup::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $editing = null;
        $editingFeaturedProducts = [];

        if ($editId = $request->query('edit')) {
            $editing = $current_team->businessLocations()->whereKey($editId)->first();
            if ($editing) {
                $ids = $editing->featured_product_ids ?? [];
                $editingFeaturedProducts = Product::query()
                    ->where('team_id', $current_team->id)
                    ->whereIn('id', $ids)
                    ->orderBy('name')
                    ->get(['id', 'name', 'sku'])
                    ->map(fn (Product $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                    ])
                    ->values()
                    ->all();
            }
        }

        $editingPayload = null;
        if ($editing) {
            $editingPayload = array_merge(
                (new BusinessLocationResource($editing))->resolve(),
                ['featured_products' => $editingFeaturedProducts],
            );
        }

        return Inertia::render('business-locations/Index', [
            'businessLocations' => $paginator,
            'sellingPriceGroups' => $sellingPriceGroups,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingBusinessLocation' => $editingPayload,
        ]);
    }

    public function store(StoreBusinessLocationRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $data['featured_product_ids'] = array_values(array_unique($data['featured_product_ids'] ?? []));

        $this->businessLocationService->create($current_team, $data);

        return to_route('business-locations.index', ['current_team' => $current_team])
            ->with('success', 'Business location created.');
    }

    public function update(UpdateBusinessLocationRequest $request, Team $current_team, BusinessLocation $business_location): RedirectResponse
    {
        $data = $request->validated();
        $data['featured_product_ids'] = array_values(array_unique($data['featured_product_ids'] ?? []));

        $this->businessLocationService->update($business_location, $data);

        return to_route('business-locations.index', ['current_team' => $current_team])
            ->with('success', 'Business location updated.');
    }

    public function destroy(Request $request, Team $current_team, BusinessLocation $business_location): RedirectResponse
    {
        $this->businessLocationService->delete($business_location);

        return to_route('business-locations.index', ['current_team' => $current_team])
            ->with('success', 'Business location deleted.');
    }
}
