<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class QuotationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $beforeRadio = array_merge($defaults, is_array($role->radio_options) ? $role->radio_options : []);
                $radio = $beforeRadio;

                $normalized = $this->normalizeQuotationPermissions($existing, $radio);

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
    private function normalizeQuotationPermissions(array $permissions, array &$radio): array
    {
        $mapped = [];
        $aliasMap = [
            'quotations.view_all' => 'quotation.view_all',
            'quotations.view_own' => 'quotation.view_own',
            'quotations.update' => 'quotation.update',
            'quotations.delete' => 'quotation.delete',
            'quotation.view' => 'quotation.view_all',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $normalized = $aliasMap[$permission] ?? $permission;
            $mapped[] = $normalized;

            if (in_array($normalized, ['quotation.update', 'quotation.delete'], true)) {
                $mapped[] = 'quotation.view_all';
            }
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('quotation.view_all', $mapped, true)) {
            $radio['quotation_view'] = 'quotation.view_all';
        } elseif (in_array('quotation.view_own', $mapped, true)) {
            $radio['quotation_view'] = 'quotation.view_own';
        }

        return $mapped;
    }
}
