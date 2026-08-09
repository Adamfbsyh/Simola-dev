<?php

namespace App\Support\MasterFleet;

use Illuminate\Http\Request;

final class FleetType
{
    public const LPG = 'MT_LPG';

    public const PERTASHOP = 'MT_PERTASHOP';

    public static function options(): array
    {
        return [
            self::LPG => 'MT LPG',
            self::PERTASHOP => 'MT PERTASHOP',
        ];
    }

    public static function normalize(?string $value): string
    {
        $normalized = mb_strtoupper(
            trim((string) $value),
            'UTF-8'
        );

        $normalized = (string) preg_replace(
            '/[^A-Z0-9]+/u',
            '_',
            $normalized
        );

        $normalized = trim(
            $normalized,
            '_'
        );

        return match ($normalized) {
            'MT_PERTASHOP',
            'PERTASHOP',
            'MT_PERTA_SHOP',
            'PERTA_SHOP' => self::PERTASHOP,
            default => self::LPG,
        };
    }

    public static function label(?string $value): string
    {
        return self::options()[
            self::normalize($value)
        ];
    }

    public static function current(
        ?Request $request = null
    ): string {
        if (app()->runningInConsole()) {
            return self::LPG;
        }

        $request ??= request();

        $candidate =
            $request->query(
                'fleet_type'
            );

        if (
            !is_string($candidate)
            ||
            trim($candidate) === ''
        ) {
            $candidate =
                $request->input(
                    'fleet_type'
                );
        }

        if (
            is_string($candidate)
            &&
            trim($candidate) !== ''
        ) {
            $fleetType =
                self::normalize(
                    $candidate
                );

            if ($request->hasSession()) {
                $request
                    ->session()
                    ->put(
                        'master_fleet_type',
                        $fleetType
                    );
            }

            return $fleetType;
        }

        if ($request->hasSession()) {
            return self::normalize(
                (string) $request
                    ->session()
                    ->get(
                        'master_fleet_type',
                        self::LPG
                    )
            );
        }

        return self::LPG;
    }

    public static function shouldScopeCurrentRoute(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $route =
            request()->route();

        $routeName =
            is_object($route)
            && method_exists(
                $route,
                'getName'
            )
                ? $route->getName()
                : null;

        if (!is_string($routeName)) {
            return false;
        }

        if (
            $routeName
            === 'master-fleet.index'
        ) {
            return true;
        }

        return str_starts_with(
            $routeName,
            'master-fleet.grouping.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.pc-set.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.vehicles.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.import.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.compare.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.companies.'
        )
        ||
        str_starts_with(
            $routeName,
            'master-fleet.terminals.'
        );
    }
}
