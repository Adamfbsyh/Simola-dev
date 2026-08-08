<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetGoogleSyncLog extends Model
{
    protected $fillable = [
        'sync_type',
        'status',
        'grouping_period_id',
        'target_date',
        'total_items',
        'created_items',
        'updated_items',
        'skipped_items',
        'failed_items',
        'message',
        'metadata',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'total_items' => 'integer',
            'created_items' => 'integer',
            'updated_items' => 'integer',
            'skipped_items' => 'integer',
            'failed_items' => 'integer',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
