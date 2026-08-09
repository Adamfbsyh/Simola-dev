<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterFleetCompareRow extends Model
{
    protected $fillable = [
        'batch_id', 'source_row', 'status', 'vehicle_id', 'plate_number',
        'unit_code', 'source_data', 'current_data', 'proposed_data', 'diff_data',
        'can_apply', 'apply_status', 'apply_message', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'source_data' => 'array',
            'current_data' => 'array',
            'proposed_data' => 'array',
            'diff_data' => 'array',
            'can_apply' => 'boolean',
            'applied_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MasterFleetCompareBatch::class, 'batch_id');
    }
}
