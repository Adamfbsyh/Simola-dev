<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetGroupingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'grouping_period_id',
        'vehicle_id',
        'company_id',
        'terminal_id',
        'distance_km',
        'distance_category',
        'distance_weight',

        /*
         * pc_initial sekarang berfungsi sebagai PC Lama.
         * pc_target tidak lagi digunakan.
         * pc_final adalah hasil generate atau edit manual.
         */
        'pc_initial',
        'pc_target',
        'pc_final',

        'plate_number_snapshot',
        'company_name_snapshot',
        'terminal_name_snapshot',

        'source_rotation_row',
        'source_final_row',

        'validation_status',
        'validation_notes',

        'assignment_source',
        'generated_at',

        'manually_adjusted_by',
        'manually_adjusted_at',
        'manual_adjustment_note',

        'operational_type',
        'operator_name_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'distance_weight' => 'integer',

            'pc_initial' => 'integer',
            'pc_target' => 'integer',
            'pc_final' => 'integer',

            'source_rotation_row' => 'integer',
            'source_final_row' => 'integer',

            'generated_at' => 'datetime',
            'manually_adjusted_at' => 'datetime',
        ];
    }

    public function groupingPeriod(): BelongsTo
    {
        return $this->belongsTo(
            FleetGroupingPeriod::class,
            'grouping_period_id'
        );
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            FleetVehicle::class,
            'vehicle_id'
        );
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

    public function manuallyAdjustedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'manually_adjusted_by'
        );
    }

    public function isP1(): bool
    {
        return $this->operational_type === 'P1';
    }

    public function isP2(): bool
    {
        return $this->operational_type === 'P2';
    }
}