<?php

namespace App\Http\Controllers\Taxes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxes\StoreTaxGroupRequest;
use App\Http\Requests\Taxes\UpdateTaxGroupRequest;
use App\Models\TaxGroup;
use App\Models\Team;
use App\Services\TaxGroupService;
use Illuminate\Http\RedirectResponse;

class TaxGroupController extends Controller
{
    public function __construct(
        protected TaxGroupService $taxGroupService,
    ) {}

    public function store(StoreTaxGroupRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $ids = array_map('intval', $data['tax_rate_ids']);
        unset($data['tax_rate_ids']);

        $this->taxGroupService->create($current_team, $data, $ids);

        return back()->with('success', 'Tax group created.');
    }

    public function update(
        UpdateTaxGroupRequest $request,
        Team $current_team,
        TaxGroup $tax_group,
    ): RedirectResponse {
        $data = $request->validated();
        $ids = array_map('intval', $data['tax_rate_ids']);
        unset($data['tax_rate_ids']);

        $this->taxGroupService->update($tax_group, $data, $ids);

        return back()->with('success', 'Tax group updated.');
    }

    public function destroy(Team $current_team, TaxGroup $tax_group): RedirectResponse
    {
        $this->taxGroupService->delete($tax_group);

        return back()->with('success', 'Tax group deleted.');
    }
}
