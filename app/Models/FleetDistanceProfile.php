<?php

namespace App\Models;

use App\Models\Concerns\HasFleetType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetDistanceProfile extends Model
{
        use HasFleetType;
use HasFactory;

    protected $fillable = [
        'company_id',
        'terminal_id',
        'distance_km',
        'distance_category',
        'weight',
        'distance_source',
        'calculated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'weight' => 'integer',
            'calculated_at' => 'datetime',
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
}