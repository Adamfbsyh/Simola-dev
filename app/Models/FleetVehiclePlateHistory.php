<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetVehiclePlateHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'old_plate_number',
        'new_plate_number',
        'old_normalized_plate_number',
        'new_normalized_plate_number',
        'effective_date',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            FleetVehicle::class,
            'vehicle_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}