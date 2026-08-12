<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorNoteTransferItem extends Model
{
    protected $fillable = [
        'transfer_request_id',
        'source_note_id',
        'snapshot_body',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(
            OperatorNoteTransferRequest::class,
            'transfer_request_id'
        );
    }

    public function sourceNote(): BelongsTo
    {
        return $this->belongsTo(OperatorDeviceNote::class, 'source_note_id');
    }
}
