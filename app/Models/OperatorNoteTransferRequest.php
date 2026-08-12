<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatorNoteTransferRequest extends Model
{
    protected $fillable = [
        'source_device_id',
        'target_device_id',
        'status',
        'requested_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function sourceDevice(): BelongsTo
    {
        return $this->belongsTo(OperatorDevice::class, 'source_device_id');
    }

    public function targetDevice(): BelongsTo
    {
        return $this->belongsTo(OperatorDevice::class, 'target_device_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OperatorNoteTransferItem::class, 'transfer_request_id');
    }
}
