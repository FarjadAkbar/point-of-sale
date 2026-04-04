<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'user_id',
    'surname',
    'first_name',
    'last_name',
    'email',
    'contact_no',
    'alt_number',
    'family_number',
    'crm_department',
    'crm_designation',
    'cmmsn_percent',
    'allow_login',
    'username',
    'password',
    'is_active',
    'position',
])]
class CustomerContactPerson extends Model
{
    protected $table = 'customer_contact_persons';

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'allow_login' => 'boolean',
            'is_active' => 'boolean',
            'cmmsn_percent' => 'decimal:2',
        ];
    }

    protected $hidden = [
        'password',
    ];
}
