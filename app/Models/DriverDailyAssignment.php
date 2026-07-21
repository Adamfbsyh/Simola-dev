<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DriverDailyAssignment extends Model
{
    protected $table = 'driver_daily_assignments';

    protected $fillable = [
        'source_date',
        'nopol',
        'driver_name',
        'total_distance',
        'travel_seconds',
        'stop_seconds',
        'tlpg',
        'source_row',
        'source_block',
        'raw_data',
        'synced_at',
    ];

    protected $casts = [
        'source_date' => 'date',
        'source_row' => 'integer',
        'total_distance' => 'decimal:2',
        'travel_seconds' => 'integer',
        'stop_seconds' => 'integer',
        'raw_data' => 'array',
        'synced_at' => 'datetime',
    ];

    public function scopeDateRange(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->whereBetween(
            'source_date',
            [
                $startDate,
                $endDate,
            ]
        );
    }

    public function scopeForNopol(
        Builder $query,
        string $nopol
    ): Builder {
        return $query->where(
            'nopol',
            $this->normalizeNopol($nopol)
        );
    }

    private function normalizeNopol(
        string $value
    ): string {
        $value = preg_replace(
            '/[^A-Z0-9]+/i',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim((string) $value)
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }
}