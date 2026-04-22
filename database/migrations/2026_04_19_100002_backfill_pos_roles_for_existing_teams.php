<?php

use App\Models\Team;
use App\Services\PosRoleDefaults;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Team::cursor() as $team) {
            PosRoleDefaults::ensure($team);
            $admin = $team->posRoles()->where('name', 'Admin')->first();
            if ($admin) {
                $owner = $team->owner();
                if ($owner) {
                    $team->members()->updateExistingPivot($owner->id, ['pos_role_id' => $admin->id]);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank: data migration.
    }
};
