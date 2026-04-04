<?php

namespace App\Http\Controllers\Taxes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxes\StoreTaxRateRequest;
use App\Http\Requests\Taxes\UpdateTaxRateRequest;
use App\Models\TaxRate;
use App\Models\Team;
use App\Services\TaxRateService;
use Illuminate\Http\RedirectResponse;

class TaxRateController extends Controller
{
    public function __construct(
        protected TaxRateService $taxRateService,
    ) {}

    public function store(StoreTaxRateRequest $request, Team $current_team): RedirectResponse
    {
        $this->taxRateService->create($current_team, $request->validated());

        return back()->with('success', 'Tax rate created.');
    }

    public function update(
        UpdateTaxRateRequest $request,
        Team $current_team,
        TaxRate $tax_rate,
    ): RedirectResponse {
        $this->taxRateService->update($tax_rate, $request->validated());

        return back()->with('success', 'Tax rate updated.');
    }

    public function destroy(Team $current_team, TaxRate $tax_rate): RedirectResponse
    {
        $this->taxRateService->delete($tax_rate);

        return back()->with('success', 'Tax rate deleted.');
    }
}
