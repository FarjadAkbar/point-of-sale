<?php

namespace App\Http\Controllers\VariationTemplates;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariationTemplates\StoreVariationTemplateRequest;
use App\Http\Requests\VariationTemplates\UpdateVariationTemplateRequest;
use App\Http\Requests\VariationTemplates\VariationTemplateIndexRequest;
use App\Http\Resources\VariationTemplateResource;
use App\Models\Team;
use App\Models\VariationTemplate;
use App\Services\VariationTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VariationTemplateController extends Controller
{
    public function __construct(
        protected VariationTemplateService $variationTemplateService,
    ) {}

    public function index(VariationTemplateIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->variationTemplateService->paginate($current_team, $filters);
        $paginator->through(function (VariationTemplate $t) {
            return (new VariationTemplateResource($t))->resolve();
        });

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->variationTemplates()->whereKey($editId)->first();
            $editing?->load('values');
        }

        return Inertia::render('variation-templates/Index', [
            'variationTemplates' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingVariationTemplate' => $editing ? (new VariationTemplateResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreVariationTemplateRequest $request, Team $current_team): RedirectResponse
    {
        $this->variationTemplateService->create($current_team, $request->validatedData());

        return to_route('variation-templates.index', ['current_team' => $current_team])
            ->with('success', 'Variation template created.');
    }

    public function update(UpdateVariationTemplateRequest $request, Team $current_team, VariationTemplate $variation_template): RedirectResponse
    {
        $this->variationTemplateService->update($variation_template, $request->validatedData());

        return to_route('variation-templates.index', ['current_team' => $current_team])
            ->with('success', 'Variation template updated.');
    }

    public function destroy(Request $request, Team $current_team, VariationTemplate $variation_template): RedirectResponse
    {
        $this->variationTemplateService->delete($variation_template);

        return to_route('variation-templates.index', ['current_team' => $current_team])
            ->with('success', 'Variation template deleted.');
    }
}
