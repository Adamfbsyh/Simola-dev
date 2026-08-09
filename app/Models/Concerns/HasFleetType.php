<?php

namespace App\Models\Concerns;

use App\Support\MasterFleet\FleetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasFleetType
{
    public function initializeHasFleetType(): void
    {
        $this->mergeFillable([
            'fleet_type',
        ]);
    }

    public static function bootHasFleetType(): void
    {
        static::addGlobalScope(
            'selected_fleet_type',
            function (
                Builder $builder
            ): void {
                if (
                    !FleetType::shouldScopeCurrentRoute()
                ) {
                    return;
                }

                $builder->where(
                    $builder
                        ->getModel()
                        ->qualifyColumn(
                            'fleet_type'
                        ),
                    FleetType::current()
                );
            }
        );

        static::creating(
            function (
                Model $model
            ): void {
                if (
                    !is_string(
                        $model->fleet_type
                    )
                    ||
                    trim(
                        $model->fleet_type
                    ) === ''
                ) {
                    $model->fleet_type =
                        FleetType::current();
                }
            }
        );

        static::saving(
            function (
                Model $model
            ): void {
                $model->fleet_type =
                    FleetType::normalize(
                        is_string(
                            $model->fleet_type
                        )
                            ? $model->fleet_type
                            : null
                    );
            }
        );
    }

    public function scopeForFleetType(
        Builder $query,
        ?string $fleetType
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'fleet_type'
            ),
            FleetType::normalize(
                $fleetType
            )
        );
    }

    public function fleetTypeLabel(): string
    {
        return FleetType::label(
            $this->fleet_type
        );
    }
}
