<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class K32DailyRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_date',
        'nopol',
        'tlpg',
        'event_name',
        'spreadsheet_count',
        'source_row',
        'source_sheet',
        'synced_at',
    ];

    protected $casts = [
        'source_date' => 'date',
        'spreadsheet_count' => 'integer',
        'source_row' => 'integer',
        'synced_at' => 'datetime',
    ];
}