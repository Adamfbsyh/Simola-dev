<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FleetVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'normalized_plate_number',
        'company_id',
        'unit_code',
        'effective_from',
        'effective_until',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(
            function (FleetVehicle $vehicle): void {
                $vehicle->plate_number =
                    self::formatPlateNumber(
                        $vehicle->plate_number
                    );

                $vehicle
                    ->normalized_plate_number =
                    self::normalizePlateNumber(
                        $vehicle->plate_number
                    );
            }
        );
    }

    /**
     * Mengubah nopol menjadi format tanpa spasi/tanda baca.
     *
     * Contoh:
     * AE 8518 UJ -> AE8518UJ
     */
    public static function normalizePlateNumber(
        string $value
    ): string {
        return preg_replace(
            '/[^A-Z0-9]/',
            '',
            mb_strtoupper(
                trim($value),
                'UTF-8'
            )
        );
    }

    /**
     * Membuat tampilan nopol lebih seragam.
     *
     * Contoh:
     * ae8518uj -> AE 8518 UJ
     */
    public static function formatPlateNumber(
        string $value
    ): string {
        $normalized =
            self::normalizePlateNumber(
                $value
            );

        if (
            preg_match(
                '/^([A-Z]{1,3})([0-9]{1,4})([A-Z]{0,3})$/',
                $normalized,
                $matches
            )
        ) {
            return trim(
                implode(
                    ' ',
                    array_filter([
                        $matches[1],
                        $matches[2],
                        $matches[3] ?? null,
                    ])
                )
            );
        }

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            FleetCompany::class,
            'company_id'
        );
    }

    public function plateHistories(): HasMany
    {
        return $this->hasMany(
            FleetVehiclePlateHistory::class,
            'vehicle_id'
        )
            ->orderByDesc(
                'effective_date'
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