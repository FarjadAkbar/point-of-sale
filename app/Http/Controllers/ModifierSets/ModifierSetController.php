<?php

namespace App\Http\Controllers\ModifierSets;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModifierSets\ModifierSetIndexRequest;
use App\Http\Requests\ModifierSets\StoreModifierSetRequest;
use App\Http\Requests\ModifierSets\UpdateModifierSetRequest;
use App\Http\Resources\ModifierSetResource;
use App\Models\ModifierSet;
use App\Models\Team;
use App\Services\ModifierSetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModifierSetController extends Controller
{
    public function __construct(
        protected ModifierSetService $modifierSetService,
    ) {}

    public function index(ModifierSetIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->modifierSetService->paginate($current_team, $filters);
        $paginator->through(fn (ModifierSet $s) => (new ModifierSetResource($s))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->modifierSets()->whereKey($editId)->first();
            $editing?->load('items');
        }

        return Inertia::render('settings/Modifiers', [
            'modifierSets' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingModifierSet' => $editing ? (new ModifierSetResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreModifierSetRequest $request, Team $current_team): RedirectResponse
    {
        $this->modifierSetService->create($current_team, $request->validatedData());

        return to_route('settings.modifiers.index', ['current_team' => $current_team])
            ->with('success', 'Modifier set created.');
    }

    public function update(UpdateModifierSetRequest $request, Team $current_team, ModifierSet $modifier_set): RedirectResponse
    {
        $this->modifierSetService->update($modifier_set, $request->validatedData());

        return to_route('settings.modifiers.index', ['current_team' => $current_team])
            ->with('success', 'Modifier set updated.');
    }

    public function destroy(Request $request, Team $current_team, ModifierSet $modifier_set): RedirectResponse
    {
        $this->modifierSetService->delete($modifier_set);

        return to_route('settings.modifiers.index', ['current_team' => $current_team])
            ->with('success', 'Modifier set deleted.');
    }
}
