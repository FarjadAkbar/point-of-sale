<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class DraftPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeDraftPermissions($existing, $radio);

                if ($normalized !== $existing || $radio !== $beforeRadio) {
                    $role->update([
                        'permissions' => $normalized,
                        'radio_options' => $radio,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @param  array<string, string>  $radio
     * @return array<int, string>
     */
    private function normalizeDraftPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'drafts.view_all' => 'draft.view_all',
            'drafts.view_own' => 'draft.view_own',
            'drafts.update' => 'draft.update',
            'drafts.delete' => 'draft.delete',
            'draft.view' => 'draft.view_all',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['draft.update', 'draft.delete'], true)) {
                $mapped[] = 'draft.view_all';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('draft.view_all', $mapped, true)) {
            $radio['draft_view'] = 'draft.view_all';
        } elseif (in_array('draft.view_own', $mapped, true)) {
            $radio['draft_view'] = 'draft.view_own';
        }

        return $mapped;
    }
}
