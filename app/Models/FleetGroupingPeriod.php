<?php

namespace App\Models;

use App\Models\Concerns\HasFleetType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetGroupingPeriod extends Model
{
        use HasFleetType;
use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'name',
        'effective_date',
        'status',
        'source_file_name',
        'created_by',
        'published_by',
        'published_at',
        'notes',
        'operator_count',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'published_at' => 'datetime',
            'operator_count' => 'integer',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(
            FleetImportBatch::class,
            'import_batch_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            FleetGroupingAssignment::class,
            'grouping_period_id'
        );
    }
}