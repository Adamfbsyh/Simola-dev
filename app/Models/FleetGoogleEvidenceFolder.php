<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetGoogleEvidenceFolder extends Model
{
    protected $fillable = [
        'grouping_period_id',
        'workspace_date',
        'pc_number',
        'vehicle_id',
        'plate_number_snapshot',
        'normalized_plate_number_snapshot',
        'month_folder_id',
        'date_folder_id',
        'pc_folder_id',
        'vehicle_folder_id',
        'pelanggaran_folder_id',
        'errorlog_folder_id',
        'accident_folder_id',
        'insiden_folder_id',
    ];

    protected function casts(): array
    {
        return [
            'workspace_date' => 'date',
            'pc_number' => 'integer',
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
}
