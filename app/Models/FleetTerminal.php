<?php

namespace App\Models;

use App\Models\Concerns\HasFleetType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetTerminal extends Model
{
        use HasFleetType;
use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'normalized_name',
        'latitude',
        'longitude',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (FleetTerminal $terminal): void {
                $terminal->name = trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $terminal->name
                    )
                );

                $terminal->normalized_name =
                    self::normalizeName(
                        $terminal->name
                    );
            }
        );
    }

    public static function normalizeName(
        string $value
    ): string {
        return mb_strtoupper(
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $value
                )
            ),
            'UTF-8'
        );
    }

    public function companies(): HasMany
    {
        return $this->hasMany(
            FleetCompany::class,
            'default_terminal_id'
        );
    }

    public function distanceProfiles(): HasMany
    {
        return $this->hasMany(
            FleetDistanceProfile::class,
            'terminal_id'
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }
}