<?php

namespace App\Http\Controllers\Tables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tables\RestaurantTableIndexRequest;
use App\Http\Requests\Tables\StoreRestaurantTableRequest;
use App\Http\Requests\Tables\UpdateRestaurantTableRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\RestaurantTable;
use App\Models\Team;
use App\Services\RestaurantTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantTableController extends Controller
{
    public function __construct(
        protected RestaurantTableService $restaurantTableService,
    ) {}

    public function index(RestaurantTableIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->restaurantTableService->paginate($current_team, $filters);
        $paginator->through(fn (RestaurantTable $t) => (new RestaurantTableResource($t))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->restaurantTables()->whereKey($editId)->first();
            $editing?->load('businessLocation:id,name');
        }

        $locations = $current_team->businessLocations()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('settings/Tables', [
            'tables' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'businessLocations' => $locations,
            'editingTable' => $editing ? (new RestaurantTableResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreRestaurantTableRequest $request, Team $current_team): RedirectResponse
    {
        $this->restaurantTableService->create($current_team, $request->validated());

        return to_route('settings.tables.index', ['current_team' => $current_team])
            ->with('success', 'Table created.');
    }

    public function update(UpdateRestaurantTableRequest $request, Team $current_team, RestaurantTable $restaurant_table): RedirectResponse
    {
        $this->restaurantTableService->update($restaurant_table, $request->validated());

        return to_route('settings.tables.index', ['current_team' => $current_team])
            ->with('success', 'Table updated.');
    }

    public function destroy(Request $request, Team $current_team, RestaurantTable $restaurant_table): RedirectResponse
    {
        $this->restaurantTableService->delete($restaurant_table);

        return to_route('settings.tables.index', ['current_team' => $current_team])
            ->with('success', 'Table deleted.');
    }
}
