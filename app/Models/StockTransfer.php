<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'team_id',
        'transaction_date',
        'ref_no',
        'status',
        'from_business_location_id',
        'to_business_location_id',
        'shipping_charges',
        'additional_notes',
        'final_total',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'shipping_charges' => 'decimal:4',
            'final_total' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'from_business_location_id');
    }

    /**
     * @return BelongsTo<BusinessLocation, $this>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'to_business_location_id');
    }

    /**
     * @return HasMany<StockTransferLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam($query, Team $team)
    {
        return $query->where('team_id', $team->id);
    }
}
