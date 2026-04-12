<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supplier_id',
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
class SupplierContactPerson extends Model
{
    protected $table = 'supplier_contact_persons';

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
