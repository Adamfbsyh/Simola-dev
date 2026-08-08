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
        'operational_type',
        'operator_name',
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
                        (string) $vehicle->plate_number
                    );

                $vehicle->normalized_plate_number =
                    self::normalizePlateNumber(
                        $vehicle->plate_number
                    );

                $vehicle->operational_type =
                    in_array(
                        $vehicle->operational_type,
                        [
                            self::TYPE_P1,
                            self::TYPE_P2,
                        ],
                        true
                    )
                        ? $vehicle->operational_type
                        : self::TYPE_P2;

                $vehicle->operator_name =
                    $vehicle->operator_name !== null
                    && trim($vehicle->operator_name) !== ''
                        ? mb_strtoupper(
                            trim($vehicle->operator_name),
                            'UTF-8'
                        )
                        : null;

                /*
                * Kendaraan P1 tidak mempunyai SPBE tujuan tetap.
                */
                if ($vehicle->operational_type === self::TYPE_P1) {
                    $vehicle->company_id = null;
                } else {
                    /*
                     * Kendaraan P2 tidak menggunakan nama operator P1.
                     */
                    $vehicle->operator_name = null;
                }
            }
        );
    }

    public static function normalizePlateNumber(
        string $value
    ): string {
        return (string) preg_replace(
            '/[^A-Z0-9]/',
            '',
            mb_strtoupper(
                trim($value),
                'UTF-8'
            )
        );
    }

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
                (string) preg_replace(
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
        return $this
            ->hasMany(
                FleetVehiclePlateHistory::class,
                'vehicle_id'
            )
            ->orderByDesc('effective_date')
            ->orderByDesc('id');
    }

    public function groupingAssignments(): HasMany
    {
        return $this->hasMany(
            FleetGroupingAssignment::class,
            'vehicle_id'
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

    public const TYPE_P1 = 'P1';

    public const TYPE_P2 = 'P2';

    public function isP1(): bool
    {
        return $this->operational_type === self::TYPE_P1;
    }

    public function isP2(): bool
    {
        return $this->operational_type === self::TYPE_P2;
    }

    public function operationalTypeLabel(): string
    {
        return match ($this->operational_type) {
            self::TYPE_P1 => 'P1 — Tujuan Fleksibel',
            self::TYPE_P2 => 'P2 — SPBE Tujuan Tetap',
            default => 'Belum Ditentukan',
        };
    }
}