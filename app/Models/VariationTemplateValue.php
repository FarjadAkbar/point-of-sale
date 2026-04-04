<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['variation_template_id', 'value', 'sort_order'])]
class VariationTemplateValue extends Model
{
    /**
     * @return BelongsTo<VariationTemplate, $this>
     */
    public function variationTemplate(): BelongsTo
    {
        return $this->belongsTo(VariationTemplate::class);
    }
}
