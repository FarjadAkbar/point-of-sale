<?php

namespace App\Http\Controllers\Taxes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxes\TaxesIndexRequest;
use App\Http\Resources\TaxGroupResource;
use App\Http\Resources\TaxRateResource;
use App\Models\TaxGroup;
use App\Models\TaxRate;
use App\Models\Team;
use App\Services\TaxGroupService;
use App\Services\TaxRateService;
use Inertia\Inertia;
use Inertia\Response;

class TaxesController extends Controller
{
    public function __construct(
        protected TaxRateService $taxRateService,
        protected TaxGroupService $taxGroupService,
    ) {}

    public function index(TaxesIndexRequest $request, Team $current_team): Response
    {
        $rateFilters = $request->rateFilters();
        $groupFilters = $request->groupFilters();

        $ratesPaginator = $this->taxRateService->paginate($current_team, $rateFilters, 'rate_page');
        $ratesPaginator->through(fn (TaxRate $r) => (new TaxRateResource($r))->resolve());

        $groupsPaginator = $this->taxGroupService->paginate($current_team, $groupFilters, 'group_page');
        $groupsPaginator->through(function (TaxGroup $g) {
            $g->load(['taxRates' => fn ($q) => $q->orderBy('tax_group_tax_rate.position')]);

            return (new TaxGroupResource($g))->resolve();
        });

        $editingRate = null;
        if ($editRateId = $request->query('edit_rate')) {
            $editingRate = $current_team->taxRates()->whereKey($editRateId)->first();
        }

        $editingGroup = null;
        if ($editGroupId = $request->query('edit_group')) {
            $editingGroup = $current_team->taxGroups()
                ->whereKey($editGroupId)
                ->with(['taxRates' => fn ($q) => $q->orderBy('tax_group_tax_rate.position')])
                ->first();
        }

        $taxRateOptions = TaxRate::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        return Inertia::render('taxes/Index', [
            'taxRates' => $ratesPaginator,
            'taxGroups' => $groupsPaginator,
            'rateFilters' => [
                'search' => $rateFilters['search'] ?? '',
                'sort' => $rateFilters['sort'] ?? 'created_at',
                'direction' => $rateFilters['direction'] ?? 'desc',
                'per_page' => $rateFilters['per_page'] ?? 15,
            ],
            'groupFilters' => [
                'search' => $groupFilters['search'] ?? '',
                'sort' => $groupFilters['sort'] ?? 'created_at',
                'direction' => $groupFilters['direction'] ?? 'desc',
                'per_page' => $groupFilters['per_page'] ?? 15,
            ],
            'editingTaxRate' => $editingRate ? (new TaxRateResource($editingRate))->resolve() : null,
            'editingTaxGroup' => $editingGroup ? (new TaxGroupResource($editingGroup))->resolve() : null,
            'taxRateOptions' => TaxRateResource::collection($taxRateOptions)->resolve(),
        ]);
    }
}
