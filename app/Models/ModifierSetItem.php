<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['modifier_set_id', 'name', 'price', 'sort_order'])]
class ModifierSetItem extends Model
{
    /**
     * @return BelongsTo<ModifierSet, $this>
     */
    public function modifierSet(): BelongsTo
    {
        return $this->belongsTo(ModifierSet::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
        ];
    }
}
