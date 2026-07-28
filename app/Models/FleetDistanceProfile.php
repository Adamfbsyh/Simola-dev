<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetDistanceProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'terminal_id',
        'distance_km',
        'distance_category',
        'weight',
        'distance_source',
        'last_verified_at',
        'is_active',
        'route_notes',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'weight' => 'integer',
            'last_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            FleetCompany::class,
            'company_id'
        );
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(
            FleetTerminal::class,
            'terminal_id'
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }
}