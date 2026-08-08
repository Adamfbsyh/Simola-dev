<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetGoogleK302DailyFile extends Model
{
    protected $fillable = [
        'grouping_period_id',
        'workspace_date',
        'month_folder_id',
        'date_folder_id',
        'template_spreadsheet_id',
        'spreadsheet_id',
        'spreadsheet_name',
        'spreadsheet_url',
        'status',
        'last_synced_at',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'workspace_date' => 'date',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function groupingPeriod(): BelongsTo
    {
        return $this->belongsTo(
            FleetGroupingPeriod::class,
            'grouping_period_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
