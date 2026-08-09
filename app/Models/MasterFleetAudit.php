<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterFleetAudit extends Model
{
    protected $fillable = [
        'occurred_at',
        'user_id',
        'user_name',
        'user_email',
        'fleet_type',
        'module',
        'action',
        'route_name',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'before_data',
        'after_data',
        'meta',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'before_data' => 'array',
            'after_data' => 'array',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
