<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FleetImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'original_name',
        'stored_path',
        'file_hash',
        'status',
        'analysis_json',
        'uploaded_by',
        'imported_by',
        'imported_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'analysis_json' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'imported_by'
        );
    }

    public function groupingPeriod(): HasOne
    {
        return $this->hasOne(
            FleetGroupingPeriod::class,
            'import_batch_id'
        );
    }
}