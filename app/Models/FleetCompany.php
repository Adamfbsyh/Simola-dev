<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'normalized_name',
        'default_terminal_id',
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
            function (FleetCompany $company): void {
                $company->name = trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $company->name
                    )
                );

                $company->normalized_name =
                    self::normalizeName(
                        $company->name
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

    public function defaultTerminal(): BelongsTo
    {
        return $this->belongsTo(
            FleetTerminal::class,
            'default_terminal_id'
        );
    }

    public function distanceProfiles(): HasMany
    {
        return $this->hasMany(
            FleetDistanceProfile::class,
            'company_id'
        );
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(
            FleetVehicle::class,
            'company_id'
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