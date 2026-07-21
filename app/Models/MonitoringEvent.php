<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringEvent extends Model
{
    protected $fillable = [
        'report_upload_id',
        'errorlog_source_id',
        'source_key',
        'event_type',
        'event_date',
        'event_time',
        'nopol',
        'driver_name',
        'tlpg',
        'event_name',
        'category',
        'severity',
        'score_impact',
        'source_page',
        'source_row',
        'evidence_link',
        'description',
        'ticket_number',
        'event_status',
        'follow_up_status',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function reportUpload()
    {
        return $this->belongsTo(ReportUpload::class);
    }
}