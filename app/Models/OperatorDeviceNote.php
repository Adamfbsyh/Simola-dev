<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorDeviceNote extends Model
{
    protected $fillable = [
        'device_id',
        'body',
        'source_note_id',
        'source_device_id',
        'delivered_from_transfer_id',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(OperatorDevice::class, 'device_id');
    }

    public function sourceDevice(): BelongsTo
    {
        return $this->belongsTo(OperatorDevice::class, 'source_device_id');
    }
}
