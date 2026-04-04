<?php

namespace App\Services;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

class ContactPersonUserService
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function syncLinkedUser(Team $team, array $row, TeamRole $portalRole = TeamRole::Supplier): ?int
    {
        $allowLogin = filter_var($row['allow_login'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $existingUserId = isset($row['user_id']) ? (int) $row['user_id'] : null;

        if (! $allowLogin) {
            if ($existingUserId) {
                $this->detachUserFromTeam($existingUserId, $team);
            }

            return null;
        }

        $name = trim(collect([$row['first_name'] ?? '', $row['last_name'] ?? ''])->filter()->implode(' '));
        if ($name === '') {
            $name = (string) ($row['username'] ?? 'Contact');
        }

        $email = $row['email'] ?? null;
        $username = $row['username'] ?? null;
        $isActive = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($existingUserId) {
            $user = User::query()->find($existingUserId);
            if (! $user) {
                return null;
            }

            $user->update([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'is_active' => $isActive,
            ]);

            if (! empty($row['password'])) {
                $user->update(['password' => $row['password']]);
            }

            $this->ensureTeamMembership($user, $team, $portalRole);

            if (! $user->current_team_id) {
                $user->update(['current_team_id' => $team->id]);
            }

            return $user->id;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $row['password'],
            'is_active' => $isActive,
            'email_verified_at' => now(),
            'current_team_id' => $team->id,
        ]);

        $user->teams()->attach($team->id, ['role' => $portalRole->value]);

        return $user->id;
    }

    protected function detachUserFromTeam(int $userId, Team $team): void
    {
        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        $user->teams()->detach($team->id);

        if ($user->current_team_id === $team->id) {
            $fallback = $user->fallbackTeam($team);
            $user->update(['current_team_id' => $fallback?->id]);
        }
    }

    protected function ensureTeamMembership(User $user, Team $team, TeamRole $portalRole): void
    {
        if (! $user->belongsToTeam($team)) {
            $user->teams()->attach($team->id, ['role' => $portalRole->value]);

            return;
        }

        if ($user->teamRole($team) === TeamRole::Supplier) {
            return;
        }
    }
}
