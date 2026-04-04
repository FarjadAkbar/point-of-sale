<?php

namespace App\Http\Controllers\SellingPriceGroups;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellingPriceGroups\SellingPriceGroupIndexRequest;
use App\Http\Requests\SellingPriceGroups\StoreSellingPriceGroupRequest;
use App\Http\Requests\SellingPriceGroups\UpdateSellingPriceGroupRequest;
use App\Http\Resources\SellingPriceGroupResource;
use App\Models\SellingPriceGroup;
use App\Models\Team;
use App\Services\SellingPriceGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SellingPriceGroupController extends Controller
{
    public function __construct(
        protected SellingPriceGroupService $sellingPriceGroupService,
    ) {}

    public function index(SellingPriceGroupIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->sellingPriceGroupService->paginate($current_team, $filters);
        $paginator->through(fn (SellingPriceGroup $g) => (new SellingPriceGroupResource($g))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->sellingPriceGroups()->whereKey($editId)->first();
        }

        return Inertia::render('selling-price-groups/Index', [
            'sellingPriceGroups' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingSellingPriceGroup' => $editing ? (new SellingPriceGroupResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreSellingPriceGroupRequest $request, Team $current_team): RedirectResponse
    {
        $this->sellingPriceGroupService->create($current_team, $request->validated());

        return to_route('selling-price-groups.index', ['current_team' => $current_team])
            ->with('success', 'Selling price group created.');
    }

    public function update(UpdateSellingPriceGroupRequest $request, Team $current_team, SellingPriceGroup $selling_price_group): RedirectResponse
    {
        $this->sellingPriceGroupService->update($selling_price_group, $request->validated());

        return to_route('selling-price-groups.index', ['current_team' => $current_team])
            ->with('success', 'Selling price group updated.');
    }

    public function destroy(Request $request, Team $current_team, SellingPriceGroup $selling_price_group): RedirectResponse
    {
        $this->sellingPriceGroupService->delete($selling_price_group);

        return to_route('selling-price-groups.index', ['current_team' => $current_team])
            ->with('success', 'Selling price group deleted.');
    }
}
