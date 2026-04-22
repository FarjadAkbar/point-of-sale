<?php

namespace App\Http\Controllers\PosRoles;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosRoles\StorePosRoleRequest;
use App\Http\Requests\PosRoles\UpdatePosRoleRequest;
use App\Models\PosRole;
use App\Models\Team;
use App\Models\User;
use App\Support\PosPermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosRoleController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $roles = $current_team->posRoles()->orderBy('name')->get();

        return Inertia::render('pos-roles/Index', [
            'posRoles' => $roles,
        ]);
    }

    public function create(Request $request, Team $current_team): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        return Inertia::render('pos-roles/Create', [
            'permissionGroups' => PosPermissionCatalog::groups(),
            'defaults' => [
                'permissions' => [],
                'radio_options' => PosPermissionCatalog::defaultRadioSelections(),
            ],
        ]);
    }

    public function store(StorePosRoleRequest $request, Team $current_team): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $data = $request->validated();
        $current_team->posRoles()->create([
            'name' => $data['name'],
            'is_service_staff' => $data['is_service_staff'] ?? false,
            'permissions' => $data['permissions'] ?? [],
            'radio_options' => $data['radio_options'] ?? PosPermissionCatalog::defaultRadioSelections(),
            'is_locked' => false,
        ]);

        return to_route('pos-roles.index', ['current_team' => $current_team->slug])
            ->with('success', 'Role created.');
    }

    public function edit(Request $request, Team $current_team, PosRole $pos_role): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        return Inertia::render('pos-roles/Edit', [
            'posRole' => $pos_role,
            'permissionGroups' => PosPermissionCatalog::groups(),
            'defaults' => [
                'permissions' => $pos_role->permissions ?? [],
                'radio_options' => array_merge(
                    PosPermissionCatalog::defaultRadioSelections(),
                    $pos_role->radio_options ?? [],
                ),
            ],
        ]);
    }

    public function update(UpdatePosRoleRequest $request, Team $current_team, PosRole $pos_role): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        abort_if($pos_role->is_locked, 403);

        $data = $request->validated();
        $pos_role->update([
            'name' => $data['name'],
            'is_service_staff' => $data['is_service_staff'] ?? false,
            'permissions' => $data['permissions'] ?? [],
            'radio_options' => $data['radio_options'] ?? PosPermissionCatalog::defaultRadioSelections(),
        ]);

        return to_route('pos-roles.index', ['current_team' => $current_team->slug])
            ->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Team $current_team, PosRole $pos_role): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        abort_if($pos_role->is_locked, 403);

        if ($pos_role->memberships()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $pos_role->delete();

        return to_route('pos-roles.index', ['current_team' => $current_team->slug])
            ->with('success', 'Role deleted.');
    }

    private function authorizeTeamAdmin(?User $user, Team $team): void
    {
        $role = $user?->teamRole($team);
        abort_unless($role && $role->isAtLeast(TeamRole::Admin), 403);
    }
}
