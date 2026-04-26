<?php

namespace Database\Seeders;

use App\Models\PosRole;
use Illuminate\Database\Seeder;

class SettingsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $normalized = $this->normalizeSettingsPermissions($existing);

                if ($normalized !== $existing) {
                    $role->update(['permissions' => $normalized]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @return array<int, string>
     */
    private function normalizeSettingsPermissions(array $permissions): array
    {
        $mapped = [];
        $aliasMap = [
            'settings.access' => 'business_settings.access',
            'business.access' => 'business_settings.access',
            'barcode.access' => 'barcode_settings.access',
            'invoice.access' => 'invoice_settings.access',
            'printer.access' => 'access_printers',
            'printers.access' => 'access_printers',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $mapped[] = $aliasMap[$permission] ?? $permission;
        }

        return array_values(array_unique($mapped));
    }
}
