<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetVehicle;
use App\Support\MasterFleet\FleetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FleetTypeController extends Controller
{
    public function index(
        Request $request
    ): View {
        $search =
            trim(
                (string) $request->query(
                    'q',
                    ''
                )
            );

        $filter =
            trim(
                (string) $request->query(
                    'type',
                    ''
                )
            );

        $base =
            FleetVehicle::query()
                ->withoutGlobalScope(
                    'selected_fleet_type'
                );

        $statistics = [
            'total' =>
                (clone $base)
                    ->count(),
            'lpg' =>
                (clone $base)
                    ->where(
                        'fleet_type',
                        FleetType::LPG
                    )
                    ->count(),
            'pertashop' =>
                (clone $base)
                    ->where(
                        'fleet_type',
                        FleetType::PERTASHOP
                    )
                    ->count(),
        ];

        $vehicles =
            FleetVehicle::query()
                ->withoutGlobalScope(
                    'selected_fleet_type'
                )
                ->with([
                    'company',
                ])
                ->when(
                    $search !== '',
                    function (
                        $query
                    ) use ($search): void {
                        $normalized =
                            FleetVehicle::normalizePlateNumber(
                                $search
                            );

                        $query->where(
                            function (
                                $nested
                            ) use (
                                $search,
                                $normalized
                            ): void {
                                $nested
                                    ->where(
                                        'plate_number',
                                        'like',
                                        '%'
                                        .
                                        $search
                                        .
                                        '%'
                                    );

                                if (
                                    $normalized !== ''
                                ) {
                                    $nested
                                        ->orWhere(
                                            'normalized_plate_number',
                                            'like',
                                            '%'
                                            .
                                            $normalized
                                            .
                                            '%'
                                        );
                                }

                                $nested
                                    ->orWhereHas(
                                        'company',
                                        function (
                                            $companyQuery
                                        ) use (
                                            $search
                                        ): void {
                                            $companyQuery
                                                ->where(
                                                    'name',
                                                    'like',
                                                    '%'
                                                    .
                                                    $search
                                                    .
                                                    '%'
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->when(
                    in_array(
                        $filter,
                        array_keys(
                            FleetType::options()
                        ),
                        true
                    ),
                    fn ($query) =>
                        $query->where(
                            'fleet_type',
                            $filter
                        )
                )
                ->orderBy(
                    'plate_number'
                )
                ->paginate(50)
                ->withQueryString();

        return view(
            'master-fleet.fleet-type.index',
            [
                'vehicles' =>
                    $vehicles,
                'statistics' =>
                    $statistics,
                'options' =>
                    FleetType::options(),
                'search' =>
                    $search,
                'filter' =>
                    $filter,
            ]
        );
    }

    public function update(
        Request $request,
        FleetVehicle $vehicle
    ): RedirectResponse {
        $validated =
            $request->validate([
                'fleet_type' => [
                    'required',
                    Rule::in(
                        array_keys(
                            FleetType::options()
                        )
                    ),
                ],
            ]);

        $vehicle->fleet_type =
            FleetType::normalize(
                $validated[
                    'fleet_type'
                ]
            );

        $vehicle->save();

        return back()->with(
            'success',
            'Jenis armada '
            .
            $vehicle->plate_number
            .
            ' diubah menjadi '
            .
            FleetType::label(
                $vehicle->fleet_type
            )
            .
            '.'
        );
    }
}
