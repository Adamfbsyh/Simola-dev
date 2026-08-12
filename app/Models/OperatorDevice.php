<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatorDevice extends Model
{
    protected $fillable = [
        'fleet_type',
        'pc_number',
        'label',
        'device_token_hash',
        'activation_code',
        'activation_expires_at',
        'activated_at',
        'last_seen_at',
        'released_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'pc_number' => 'integer',
            'activation_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'released_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OperatorDeviceNote::class, 'device_id');
    }

    public function displayFleetType(): string
    {
        return $this->fleet_type === 'MT_PERTASHOP'
            ? 'MT PERTASHOP'
            : 'MT LPG';
    }

    public function displayName(): string
    {
        return $this->displayFleetType() . ' · PC ' . $this->pc_number;
    }
}
