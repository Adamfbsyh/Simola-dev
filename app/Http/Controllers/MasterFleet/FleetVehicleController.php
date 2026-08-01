<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetCompany;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetVehicle;
use App\Models\FleetVehiclePlateHistory;
use App\Services\MasterFleet\FleetVehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class FleetVehicleController extends Controller
{
    public function index(
        Request $request
    ): View {
        $validated =
            $request->validate([
                'q' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'status' => [
                    'nullable',
                    Rule::in([
                        'active',
                        'inactive',
                    ]),
                ],

                'company_id' => [
                    'nullable',
                    'integer',
                    'exists:fleet_companies,id',
                ],

                'per_page' => [
                    'nullable',
                    Rule::in([
                        '25',
                        '50',
                        '100',
                    ]),
                ],
            ]);

        $query =
            FleetVehicle::query()
                ->with([
                    'company:id,name',
                ])
                ->withCount(
                    'plateHistories'
                );

        $search =
            trim(
                (string) (
                    $validated['q']
                    ?? ''
                )
            );

        if ($search !== '') {
            $normalizedSearch =
                FleetVehicle::normalizePlateNumber(
                    $search
                );

            $query->where(
                function ($subQuery) use (
                    $search,
                    $normalizedSearch
                ): void {
                    $subQuery
                        ->where(
                            'plate_number',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'normalized_plate_number',
                            'like',
                            '%' . $normalizedSearch . '%'
                        )
                        ->orWhere(
                            'unit_code',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'company',
                            function ($companyQuery) use (
                                $search
                            ): void {
                                $companyQuery->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                );
                            }
                        )
                        ->orWhereHas(
                            'plateHistories',
                            function ($historyQuery) use (
                                $search,
                                $normalizedSearch
                            ): void {
                                $historyQuery
                                    ->where(
                                        'old_plate_number',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'new_plate_number',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'old_normalized_plate_number',
                                        'like',
                                        '%' . $normalizedSearch . '%'
                                    )
                                    ->orWhere(
                                        'new_normalized_plate_number',
                                        'like',
                                        '%' . $normalizedSearch . '%'
                                    );
                            }
                        );
                }
            );
        }

        if (
            ($validated['status'] ?? null)
            === 'active'
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        if (
            ($validated['status'] ?? null)
            === 'inactive'
        ) {
            $query->where(
                'is_active',
                false
            );
        }

        if (!empty($validated['company_id'])) {
            $query->where(
                'company_id',
                (int) $validated[
                    'company_id'
                ]
            );
        }

        $perPage =
            (int) (
                $validated['per_page']
                ?? 25
            );

        $vehicles =
            $query
                ->orderByDesc('is_active')
                ->orderBy('plate_number')
                ->paginate($perPage)
                ->withQueryString();

        $vehicleIds =
            $vehicles
                ->getCollection()
                ->pluck('id');

        $publishedPeriod =
            FleetGroupingPeriod::query()
                ->where(
                    'status',
                    'published'
                )
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

        $draftPeriod =
            FleetGroupingPeriod::query()
                ->where(
                    'status',
                    'draft'
                )
                ->orderByDesc('id')
                ->first();

        $publishedAssignments =
            collect();

        $draftAssignments =
            collect();

        if (
            $publishedPeriod
            &&
            $vehicleIds->isNotEmpty()
        ) {
            $publishedAssignments =
                FleetGroupingAssignment::query()
                    ->where(
                        'grouping_period_id',
                        $publishedPeriod->id
                    )
                    ->whereIn(
                        'vehicle_id',
                        $vehicleIds
                    )
                    ->get()
                    ->keyBy('vehicle_id');
        }

        if (
            $draftPeriod
            &&
            $vehicleIds->isNotEmpty()
        ) {
            $draftAssignments =
                FleetGroupingAssignment::query()
                    ->where(
                        'grouping_period_id',
                        $draftPeriod->id
                    )
                    ->whereIn(
                        'vehicle_id',
                        $vehicleIds
                    )
                    ->get()
                    ->keyBy('vehicle_id');
        }

        $companies =
            FleetCompany::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'is_active',
                ]);

        return view(
            'master-fleet.vehicles.index',
            [
                'vehicles' =>
                    $vehicles,

                'companies' =>
                    $companies,

                'publishedPeriod' =>
                    $publishedPeriod,

                'draftPeriod' =>
                    $draftPeriod,

                'publishedAssignments' =>
                    $publishedAssignments,

                'draftAssignments' =>
                    $draftAssignments,

                'totalCount' =>
                    FleetVehicle::query()
                        ->count(),

                'activeCount' =>
                    FleetVehicle::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->count(),

                'inactiveCount' =>
                    FleetVehicle::query()
                        ->where(
                            'is_active',
                            false
                        )
                        ->count(),

                'historyCount' =>
                    FleetVehiclePlateHistory::query()
                        ->count(),

                'filters' => [
                    'q' =>
                        $search,

                    'status' =>
                        $validated['status']
                        ?? '',

                    'company_id' =>
                        $validated['company_id']
                        ?? '',

                    'per_page' =>
                        $perPage,
                ],
            ]
        );
    }

    public function create(): View
    {
        return view(
            'master-fleet.vehicles.create',
            [
                'vehicle' =>
                    new FleetVehicle([
                        'is_active' => true,
                        'effective_from' =>
                            now()->toDateString(),
                    ]),

                'companies' =>
                    $this->companies(),
            ]
        );
    }

    public function store(
        Request $request,
        FleetVehicleService $service
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request
            );

        try {
            $vehicle =
                $service->create(
                    $validated
                );

            return redirect()
                ->route(
                    'master-fleet.vehicles.edit',
                    $vehicle
                )
                ->with(
                    'success',
                    'Kendaraan berhasil ditambahkan.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function edit(
        FleetVehicle $vehicle
    ): View {
        $vehicle->load([
            'company:id,name',
            'plateHistories.changedBy:id,name,email',
        ]);

        $currentAssignments =
            FleetGroupingAssignment::query()
                ->with([
                    'groupingPeriod:id,name,status,published_at',
                    'terminal:id,name',
                    'company:id,name',
                ])
                ->where(
                    'vehicle_id',
                    $vehicle->id
                )
                ->whereHas(
                    'groupingPeriod',
                    function ($query): void {
                        $query->whereIn(
                            'status',
                            [
                                'published',
                                'draft',
                            ]
                        );
                    }
                )
                ->get();

        return view(
            'master-fleet.vehicles.edit',
            [
                'vehicle' =>
                    $vehicle,

                'companies' =>
                    $this->companies(),

                'currentAssignments' =>
                    $currentAssignments,
            ]
        );
    }

    public function update(
        Request $request,
        FleetVehicle $vehicle,
        FleetVehicleService $service
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request,
                true
            );

        try {
            $updatedVehicle =
                $service->update(
                    vehicle:
                        $vehicle,

                    data:
                        $validated,

                    userId:
                        (int) $request
                            ->user()
                            ->id
                );

            return redirect()
                ->route(
                    'master-fleet.vehicles.edit',
                    $updatedVehicle
                )
                ->with(
                    'success',
                    'Data kendaraan berhasil diperbarui.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function history(
        FleetVehicle $vehicle
    ): View {
        $vehicle->load([
            'company:id,name',
            'plateHistories.changedBy:id,name,email',
        ]);

        return view(
            'master-fleet.vehicles.history',
            compact('vehicle')
        );
    }

    public function toggleActive(
        FleetVehicle $vehicle,
        FleetVehicleService $service
    ): RedirectResponse {
        try {
            $updatedVehicle =
                $service->toggleActive(
                    $vehicle
                );

            return back()->with(
                'success',
                $updatedVehicle->is_active
                    ? 'Kendaraan berhasil diaktifkan.'
                    : 'Kendaraan berhasil dinonaktifkan.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    private function validatedData(
        Request $request,
        bool $isUpdate = false
    ): array {
        $rules = [
            'plate_number' => [
                'required',
                'string',
                'max:30',
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:fleet_companies,id',
            ],

            'unit_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'effective_from' => [
                'nullable',
                'date',
            ],

            'effective_until' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];

        if ($isUpdate) {
            $rules[
                'plate_change_effective_date'
            ] = [
                'nullable',
                'date',
            ];

            $rules[
                'plate_change_reason'
            ] = [
                'nullable',
                'string',
                'max:1000',
            ];
        }

        $validated =
            $request->validate($rules);

        $validated['is_active'] =
            $request->boolean('is_active');

        return $validated;
    }

    private function companies()
    {
        return FleetCompany::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'is_active',
            ]);
    }
}