<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterFleetCompareBatch extends Model
{
    protected $fillable = [
        'uuid', 'fleet_type', 'original_name', 'stored_path', 'source_hash',
        'status', 'sheet_name', 'header_row', 'header_map', 'summary',
        'uploaded_by', 'applied_by', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'header_map' => 'array',
            'summary' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function rows(): HasMany
    {
        return $this->hasMany(MasterFleetCompareRow::class, 'batch_id');
    }
}
