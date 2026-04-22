<?php

namespace App\Services;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class TeamUserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): User
    {
        return DB::transaction(function () use ($team, $data) {
            if (User::query()->where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => __('A user with this email already exists.'),
                ]);
            }

            $name = trim($data['first_name'].' '.($data['last_name'] ?? ''));
            if ($name === '') {
                $name = $data['email'];
            }

            /** @var User $user */
            $user = User::query()->create([
                'name' => $name,
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $settings = $this->buildSettings($data);

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Member,
                'pos_role_id' => $data['pos_role_id'],
                'settings' => $settings,
            ]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Membership $membership, array $data): void
    {
        DB::transaction(function () use ($membership, $data) {
            $user = $membership->user;
            $name = trim(($data['first_name'] ?? explode(' ', $user->name)[0]).' '.($data['last_name'] ?? ''));
            if ($name !== '') {
                $user->name = $name;
            }
            $user->email = $data['email'];
            $user->username = $data['username'] ?? null;
            $user->is_active = $data['is_active'] ?? true;
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            $membership->pos_role_id = $data['pos_role_id'];
            $membership->settings = $this->buildSettings($data, $membership->settings ?? []);
            $membership->save();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function buildSettings(array $data, array $existing = []): array
    {
        $pin = $data['service_staff_pin'] ?? null;
        $pinHash = $pin
            ? Hash::make($pin)
            : ($existing['service_staff_pin_hash'] ?? null);

        $settings = [
            'prefix' => $data['prefix'] ?? null,
            'is_enable_service_staff_pin' => (bool) ($data['is_enable_service_staff_pin'] ?? false),
            'service_staff_pin_hash' => $pinHash,
            'allow_login' => (bool) ($data['allow_login'] ?? true),
            'access_all_locations' => (bool) ($data['access_all_locations'] ?? true),
            'location_ids' => $data['location_ids'] ?? [],
            'cmmsn_percent' => $data['cmmsn_percent'] ?? null,
            'max_sales_discount_percent' => $data['max_sales_discount_percent'] ?? null,
            'selected_contacts' => (bool) ($data['selected_contacts'] ?? false),
            'profile' => $data['profile'] ?? [],
        ];

        if (empty($settings['service_staff_pin_hash'])) {
            unset($settings['service_staff_pin_hash']);
        }

        return $settings;
    }

    public function assertCanRemove(Membership $membership): void
    {
        if ($membership->role === TeamRole::Owner) {
            throw ValidationException::withMessages([
                'user' => __('The team owner cannot be removed.'),
            ]);
        }
    }
}
