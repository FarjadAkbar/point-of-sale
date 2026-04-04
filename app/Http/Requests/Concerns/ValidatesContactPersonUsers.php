<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Illuminate\Validation\Validator;

trait ValidatesContactPersonUsers
{
    protected function withContactPersonUserValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $persons = $this->input('contact_persons', []);
            if (! is_array($persons)) {
                return;
            }
            foreach ($persons as $i => $p) {
                if (! is_array($p)) {
                    continue;
                }
                if (! filter_var($p['allow_login'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                if (empty($p['email'])) {
                    $validator->errors()->add("contact_persons.$i.email", 'Email is required when allow login is enabled.');
                }
                if (empty($p['username'])) {
                    $validator->errors()->add("contact_persons.$i.username", 'Username is required when allow login is enabled.');
                }
                $uid = isset($p['user_id']) ? (int) $p['user_id'] : 0;
                if ($uid <= 0 && empty($p['password'])) {
                    $validator->errors()->add("contact_persons.$i.password", 'Password is required when allow login is enabled for a new login.');
                }
                if (! empty($p['email'])) {
                    $q = User::query()->where('email', $p['email']);
                    if ($uid > 0) {
                        $q->where('id', '!=', $uid);
                    }
                    if ($q->exists()) {
                        $validator->errors()->add("contact_persons.$i.email", 'This email is already registered.');
                    }
                }
                if (! empty($p['username'])) {
                    $q = User::query()->where('username', $p['username']);
                    if ($uid > 0) {
                        $q->where('id', '!=', $uid);
                    }
                    if ($q->exists()) {
                        $validator->errors()->add("contact_persons.$i.username", 'This username is already taken.');
                    }
                }
            }
        });
    }
}
