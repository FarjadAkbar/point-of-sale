<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    /**
     * Create a new team and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $team = Team::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $adminPosRole = $team->posRoles()->where('name', 'Admin')->first();

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
                'pos_role_id' => $adminPosRole?->id,
            ]);

            $user->switchTeam($team);

            return $team;
        });
    }
}
