<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetGoogleAccount extends Model
{
    public const PURPOSE_K302 = 'k302';

    public const PURPOSE_EVIDENCE = 'evidence';

    protected $fillable = [
        'user_id',
        'purpose',
        'google_email',
        'token_payload',
        'scopes',
        'connected_at',
        'last_refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'token_payload' => 'encrypted:array',
            'scopes' => 'array',
            'connected_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    public static function purposes(): array
    {
        return [
            self::PURPOSE_K302,
            self::PURPOSE_EVIDENCE,
        ];
    }

    public function purposeLabel(): string
    {
        return match ($this->purpose) {
            self::PURPOSE_K302 => 'K3-02',
            self::PURPOSE_EVIDENCE => 'Evidence',
            default => strtoupper((string) $this->purpose),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
