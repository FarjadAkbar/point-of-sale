<?php

namespace App\Http\Controllers\TeamUsers;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUsers\StoreTeamUserRequest;
use App\Http\Requests\TeamUsers\UpdateTeamUserRequest;
use App\Models\BusinessLocation;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamUserController extends Controller
{
    public function __construct(
        protected TeamUserService $teamUserService,
    ) {}

    public function index(Request $request, Team $current_team): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $paginator = Membership::query()
            ->where('team_id', $current_team->id)
            ->with(['user', 'posRole'])
            ->orderBy('id')
            ->paginate(15);

        $paginator->setCollection(
            $paginator->getCollection()->map(function (Membership $m) {
                $u = $m->user;
                $parts = explode(' ', $u->name, 2);

                return [
                    'membership_id' => $m->id,
                    'user_id' => $u->id,
                    'first_name' => $parts[0] ?? $u->name,
                    'last_name' => $parts[1] ?? null,
                    'email' => $u->email,
                    'is_active' => $u->is_active,
                    'team_role' => $m->role->value,
                    'pos_role' => $m->posRole ? ['id' => $m->posRole->id, 'name' => $m->posRole->name] : null,
                ];
            }),
        );

        return Inertia::render('team-users/Index', [
            'memberships' => $paginator,
        ]);
    }

    public function create(Request $request, Team $current_team): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        return Inertia::render('team-users/Create', [
            'posRoles' => $current_team->posRoles()->orderBy('name')->get(['id', 'name', 'is_locked']),
            'locations' => BusinessLocation::query()
                ->where('team_id', $current_team->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreTeamUserRequest $request, Team $current_team): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $this->teamUserService->create($current_team, $request->validated());

        return to_route('team-users.index', ['current_team' => $current_team->slug])
            ->with('success', 'User added to the team.');
    }

    public function edit(Request $request, Team $current_team, User $user): Response
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $membership = Membership::query()
            ->where('team_id', $current_team->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $parts = explode(' ', $user->name, 2);
        $settings = $membership->settings ?? [];

        return Inertia::render('team-users/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $parts[0] ?? $user->name,
                'last_name' => $parts[1] ?? '',
                'email' => $user->email,
                'username' => $user->username,
                'is_active' => $user->is_active,
                'team_role' => $membership->role->value,
            ],
            'settings' => $settings,
            'posRoles' => $current_team->posRoles()->orderBy('name')->get(['id', 'name', 'is_locked']),
            'pos_role_id' => $membership->pos_role_id,
            'locations' => BusinessLocation::query()
                ->where('team_id', $current_team->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTeamUserRequest $request, Team $current_team, User $user): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $membership = Membership::query()
            ->where('team_id', $current_team->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->teamUserService->update($membership, $request->validated());

        return to_route('team-users.index', ['current_team' => $current_team->slug])
            ->with('success', 'User updated.');
    }

    public function destroy(Request $request, Team $current_team, User $user): RedirectResponse
    {
        $this->authorizeTeamAdmin($request->user(), $current_team);

        $membership = Membership::query()
            ->where('team_id', $current_team->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->teamUserService->assertCanRemove($membership);

        $current_team->members()->detach($user->id);

        return to_route('team-users.index', ['current_team' => $current_team->slug])
            ->with('success', 'User removed from the team.');
    }

    private function authorizeTeamAdmin(?User $user, Team $team): void
    {
        $role = $user?->teamRole($team);
        abort_unless($role && $role->isAtLeast(TeamRole::Admin), 403);
    }
}
