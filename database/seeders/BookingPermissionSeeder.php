<?php

namespace Database\Seeders;

use App\Models\PosRole;
use App\Support\PosPermissionCatalog;
use Illuminate\Database\Seeder;

class BookingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        PosRole::query()->chunkById(100, function ($roles): void {
            foreach ($roles as $role) {
                $existing = is_array($role->permissions) ? $role->permissions : [];
                $defaults = PosPermissionCatalog::defaultRadioSelections();
                $origRadio = is_array($role->radio_options) ? $role->radio_options : [];
                $radioBefore = array_merge($defaults, $origRadio);
                $radioOptions = $radioBefore;

                $normalized = $this->normalizeBookingPermissions($existing, $radioOptions);

                if ($normalized !== $existing || $radioOptions !== $radioBefore) {
                    $role->update([
                        'permissions' => $normalized,
                        'radio_options' => $radioOptions,
                    ]);
                }
            }
        });
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @param  array<string, string>  $radioOptions
     * @return array<int, string>
     */
    private function normalizeBookingPermissions(array $permissions, array &$radioOptions): array
    {
        $mapped = [];
        $radioKey = 'bookings_view';

        $permissionToRadio = [
            'bookings.crud_all' => 'crud_all_bookings',
            'booking.crud_all' => 'crud_all_bookings',
            'bookings.view_all' => 'crud_all_bookings',
            'booking.view_all' => 'crud_all_bookings',
            'bookings.crud_own' => 'crud_own_bookings',
            'booking.crud_own' => 'crud_own_bookings',
            'bookings.view_own' => 'crud_own_bookings',
            'booking.view_own' => 'crud_own_bookings',
            'crud_all_bookings' => 'crud_all_bookings',
            'crud_own_bookings' => 'crud_own_bookings',
        ];

        foreach ($permissions as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            if (isset($permissionToRadio[$permission])) {
                $radioOptions[$radioKey] = $permissionToRadio[$permission];
                if (! in_array($permissionToRadio[$permission], $mapped, true)) {
                    $mapped[] = $permissionToRadio[$permission];
                }

                continue;
            }

            $mapped[] = $permission;
        }

        $mapped = array_values(array_unique($mapped));

        if (in_array('crud_all_bookings', $mapped, true)) {
            $radioOptions['bookings_view'] = 'crud_all_bookings';
        } elseif (in_array('crud_own_bookings', $mapped, true)) {
            $radioOptions['bookings_view'] = 'crud_own_bookings';
        }

        return $mapped;
    }
}
