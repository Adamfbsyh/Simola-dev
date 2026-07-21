<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErrorlogSheetSource extends Model
{
    protected $fillable = [
        'spreadsheet_id',
        'spreadsheet_url',
        'sheet_name',
        'year',
        'month',
        'created_by',
        'last_synced_at',
        'total_rows',
        'created_rows',
        'updated_rows',
        'status',
        'last_error',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'total_rows' => 'integer',
        'created_rows' => 'integer',
        'updated_rows' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            MonitoringEvent::class,
            'errorlog_source_id'
        );
    }
}