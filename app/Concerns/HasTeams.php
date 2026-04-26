<?php

namespace App\Concerns;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Support\TeamPermissions;
use App\Support\UserTeam;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

trait HasTeams
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot(['role', 'pos_role_id', 'settings'])
            ->withTimestamps();
    }

    /**
     * Get all of the teams the user owns.
     *
     * @return HasManyThrough<Team, Membership, $this>
     */
    public function ownedTeams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Team::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'team_id',
        )->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Switch to the given team.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->update(['current_team_id' => $team->id]);
        $this->setRelation('currentTeam', $team);

        URL::defaults(['current_team' => $team->slug]);

        return true;
    }

    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Determine if the given team is the user's current team.
     */
    public function isCurrentTeam(Team $team): bool
    {
        return (int) $this->current_team_id === (int) $team->id;
    }

    /**
     * Determine if the user is the owner of the given team.
     */
    public function ownsTeam(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::Owner;
    }

    /**
     * Get the user's role on the given team.
     */
    public function teamRole(Team $team): ?TeamRole
    {
        return $this->teamMemberships()
            ->where('team_id', $team->id)
            ->first()
            ?->role;
    }

    /**
     * Get the user's teams as a collection of UserTeam objects.
     *
     * @return Collection<int, UserTeam>
     */
    public function toUserTeams(bool $includeCurrent = false): Collection
    {
        return $this->teams()
            ->get()
            ->map(fn (Team $team) => ! $includeCurrent && $this->isCurrentTeam($team) ? null : $this->toUserTeam($team))
            ->filter()
            ->values();
    }

    /**
     * Get the user's team as a UserTeam object.
     */
    public function toUserTeam(Team $team): UserTeam
    {
        $role = $this->teamRole($team);

        return new UserTeam(
            id: $team->id,
            name: $team->name,
            slug: $team->slug,
            isPersonal: $team->is_personal,
            role: $role?->value,
            roleLabel: $role?->label(),
            isCurrent: $this->isCurrentTeam($team),
        );
    }

    /**
     * Get the standard permissions for a team as a TeamPermissions object.
     */
    public function toTeamPermissions(Team $team): TeamPermissions
    {
        $role = $this->teamRole($team);

        return new TeamPermissions(
            canUpdateTeam: $role?->hasPermission('team:update') ?? false,
            canDeleteTeam: $role?->hasPermission('team:delete') ?? false,
            canAddMember: $role?->hasPermission('member:add') ?? false,
            canUpdateMember: $role?->hasPermission('member:update') ?? false,
            canRemoveMember: $role?->hasPermission('member:remove') ?? false,
            canCreateInvitation: $role?->hasPermission('invitation:create') ?? false,
            canCancelInvitation: $role?->hasPermission('invitation:cancel') ?? false,
        );
    }

    public function fallbackTeam(?Team $excluding = null): ?Team
    {
        return $this->teams()
            ->when($excluding, fn ($query) => $query->where('teams.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(teams.name)')
            ->first();
    }

    /**
     * Determine if the user has the given permission on the team.
     */
    public function hasTeamPermission(Team $team, string $permission): bool
    {
        return $this->teamRole($team)?->hasPermission($permission) ?? false;
    }

    /**
     * @return array<int, string>
     */
    public function teamPosPermissions(Team $team): array
    {
        $membership = $this->teamMembership($team);
        $posRole = $membership?->posRole;
        if (! $posRole) {
            return [];
        }

        $checkboxes = $posRole->permissions ?? [];
        $radios = array_values(array_filter($posRole->radio_options ?? []));
        $granted = array_values(array_unique(array_merge($checkboxes, $radios)));

        return $this->withImpliedPosPermissions($granted);
    }

    public function hasPosPermission(Team $team, string $permission): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        return in_array($permission, $this->teamPosPermissions($team), true);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPosPermission(Team $team, array $permissions): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        if ($permissions === []) {
            return true;
        }

        $granted = $this->teamPosPermissions($team);
        foreach ($permissions as $permission) {
            if (in_array($permission, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    protected function teamMembership(Team $team): ?Membership
    {
        return $this->teamMemberships()
            ->where('team_id', $team->id)
            ->with('posRole')
            ->first();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    protected function withImpliedPosPermissions(array $permissions): array
    {
        $mapped = $permissions;
        $impliedListPermissions = [
            'brand.create' => 'brand.view',
            'brand.update' => 'brand.view',
            'brand.delete' => 'brand.view',
            'unit.create' => 'unit.view',
            'unit.update' => 'unit.view',
            'unit.delete' => 'unit.view',
            'warranty.create' => 'warranty.view',
            'warranty.update' => 'warranty.view',
            'warranty.delete' => 'warranty.view',
            'stock_adjustment.create' => 'stock_adjustment.view',
            'stock_adjustment.update' => 'stock_adjustment.view',
            'stock_adjustment.delete' => 'stock_adjustment.view',
            'stock_transfer.create' => 'stock_transfer.view',
            'stock_transfer.update' => 'stock_transfer.view',
            'stock_transfer.delete' => 'stock_transfer.view',
            'direct_sell.update' => 'direct_sell.view',
            'direct_sell.delete' => 'direct_sell.view',
            'draft.update' => 'draft.view_all',
            'draft.delete' => 'draft.view_all',
            'quotation.update' => 'quotation.view_all',
            'quotation.delete' => 'quotation.view_all',
            'tax_rate.create' => 'tax_rate.view',
            'tax_rate.update' => 'tax_rate.view',
            'tax_rate.delete' => 'tax_rate.view',
            'expense.add' => 'view_own_expense',
            'expense.edit' => 'view_own_expense',
            'expense.delete' => 'view_own_expense',
        ];

        foreach ($permissions as $permission) {
            if (isset($impliedListPermissions[$permission])) {
                $mapped[] = $impliedListPermissions[$permission];
            }
        }

        return array_values(array_unique($mapped));
    }
}
