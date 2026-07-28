<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetCompany;
use App\Models\FleetDistanceProfile;
use App\Models\FleetTerminal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class FleetCompanyController extends Controller
{
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->input(
                'search',
                ''
            )
        );

        $terminalId =
            $request->input(
                'terminal_id'
            );

        $status = trim(
            (string) $request->input(
                'status',
                ''
            )
        );

        $companies = FleetCompany::query()
            ->with([
                'defaultTerminal',
                'distanceProfiles',
            ])
            ->withCount('vehicles')
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($subQuery) use ($search) {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                filled($terminalId),
                fn ($query) =>
                    $query->where(
                        'default_terminal_id',
                        $terminalId
                    )
            )
            ->when(
                $status === 'active',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->when(
                $status === 'inactive',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        false
                    )
            )
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $terminals = FleetTerminal::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view(
            'master-fleet.companies.index',
            compact(
                'companies',
                'terminals',
                'search',
                'terminalId',
                'status'
            )
        );
    }

    public function create(): View
    {
        return view(
            'master-fleet.companies.create',
            [
                'company' =>
                    new FleetCompany([
                        'is_active' => true,
                    ]),

                'terminals' =>
                    FleetTerminal::query()
                        ->active()
                        ->orderBy('name')
                        ->get(),

                'distanceProfile' =>
                    new FleetDistanceProfile([
                        'distance_source' =>
                            'manual',
                    ]),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request
            );

        try {
            $company = DB::transaction(
                function () use ($validated) {
                    $company =
                        FleetCompany::query()
                            ->create(
                                $validated[
                                    'company'
                                ]
                            );

                    $this->syncDistanceProfile(
                        $company,
                        $validated[
                            'distance'
                        ]
                    );

                    return $company;
                }
            );

            return redirect()
                ->route(
                    'master-fleet.companies.edit',
                    $company
                )
                ->with(
                    'success',
                    'SPBE/Perusahaan berhasil ditambahkan.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'SPBE/Perusahaan gagal ditambahkan: '
                    . $e->getMessage()
                );
        }
    }

    public function edit(
        FleetCompany $company
    ): View {
        $company->load([
            'defaultTerminal',
            'distanceProfiles',
        ]);

        $distanceProfile =
            $company
                ->distanceProfiles
                ->firstWhere(
                    'terminal_id',
                    $company
                        ->default_terminal_id
                )
            ?? new FleetDistanceProfile([
                'distance_source' =>
                    'manual',
            ]);

        $terminals = FleetTerminal::query()
            ->where(
                function ($query) use ($company) {
                    $query
                        ->where(
                            'is_active',
                            true
                        )
                        ->orWhere(
                            'id',
                            $company
                                ->default_terminal_id
                        );
                }
            )
            ->orderBy('name')
            ->get();

        return view(
            'master-fleet.companies.edit',
            compact(
                'company',
                'terminals',
                'distanceProfile'
            )
        );
    }

    public function update(
        Request $request,
        FleetCompany $company
    ): RedirectResponse {
        $validated =
            $this->validatedData(
                $request,
                $company
            );

        try {
            DB::transaction(
                function () use (
                    $company,
                    $validated
                ) {
                    $company->update(
                        $validated[
                            'company'
                        ]
                    );

                    $this->syncDistanceProfile(
                        $company,
                        $validated[
                            'distance'
                        ]
                    );
                }
            );

            return redirect()
                ->route(
                    'master-fleet.companies.edit',
                    $company
                )
                ->with(
                    'success',
                    'SPBE/Perusahaan berhasil diperbarui.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'SPBE/Perusahaan gagal diperbarui: '
                    . $e->getMessage()
                );
        }
    }

    public function toggleActive(
        FleetCompany $company
    ): RedirectResponse {
        if (
            $company->is_active
            &&
            $company
                ->vehicles()
                ->where(
                    'is_active',
                    true
                )
                ->exists()
        ) {
            return back()->with(
                'error',
                'SPBE tidak dapat dinonaktifkan karena masih memiliki kendaraan aktif.'
            );
        }

        $company->forceFill([
            'is_active' =>
                !$company->is_active,
        ])->save();

        return back()->with(
            'success',
            $company->is_active
                ? 'SPBE berhasil diaktifkan.'
                : 'SPBE berhasil dinonaktifkan.'
        );
    }

    private function validatedData(
        Request $request,
        ?FleetCompany $company = null
    ): array {
        $request->merge([
            'code' =>
                $request->filled('code')
                    ? mb_strtoupper(
                        trim(
                            (string) $request->input(
                                'code'
                            )
                        ),
                        'UTF-8'
                    )
                    : null,
        ]);

        $distanceCategories =
            array_keys(
                config(
                    'master-fleet.distance_categories',
                    []
                )
            );

        $validated = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'fleet_companies',
                    'code'
                )->ignore(
                    $company?->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'default_terminal_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'fleet_terminals',
                    'id'
                ),
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'distance_km' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.99',
            ],

            'distance_category' => [
                'nullable',
                Rule::in(
                    $distanceCategories
                ),
            ],

            'weight' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'distance_source' => [
                'nullable',
                'string',
                'max:50',
            ],

            'last_verified_at' => [
                'nullable',
                'date',
            ],

            'route_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        if (
            !$validated[
                'default_terminal_id'
            ]
            &&
            (
                filled(
                    $validated[
                        'distance_km'
                    ]
                    ?? null
                )
                ||
                filled(
                    $validated[
                        'distance_category'
                    ]
                    ?? null
                )
                ||
                filled(
                    $validated[
                        'weight'
                    ]
                    ?? null
                )
            )
        ) {
            throw ValidationException::withMessages([
                'default_terminal_id' =>
                    'Pilih TLPG terlebih dahulu sebelum mengisi profil jarak.',
            ]);
        }

        $normalizedName =
            FleetCompany::normalizeName(
                $validated['name']
            );

        $duplicateQuery =
            FleetCompany::query()
                ->where(
                    'normalized_name',
                    $normalizedName
                );

        if ($company) {
            $duplicateQuery->where(
                'id',
                '!=',
                $company->id
            );
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'name' =>
                    'Nama SPBE/Perusahaan sudah digunakan.',
            ]);
        }

        return [
            'company' => [
                'code' =>
                    $validated['code']
                    ?? null,

                'name' =>
                    trim(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            $validated['name']
                        )
                    ),

                'normalized_name' =>
                    $normalizedName,

                'default_terminal_id' =>
                    $validated[
                        'default_terminal_id'
                    ]
                    ?? null,

                'latitude' =>
                    $validated['latitude']
                    ?? null,

                'longitude' =>
                    $validated['longitude']
                    ?? null,

                'is_active' =>
                    (bool) $validated[
                        'is_active'
                    ],

                'notes' =>
                    $validated['notes']
                    ?? null,
            ],

            'distance' => [
                'distance_km' =>
                    $validated[
                        'distance_km'
                    ]
                    ?? null,

                'distance_category' =>
                    $validated[
                        'distance_category'
                    ]
                    ?? null,

                'weight' =>
                    $validated['weight']
                    ?? null,

                'distance_source' =>
                    $validated[
                        'distance_source'
                    ]
                    ?? 'manual',

                'last_verified_at' =>
                    $validated[
                        'last_verified_at'
                    ]
                    ?? null,

                'route_notes' =>
                    $validated[
                        'route_notes'
                    ]
                    ?? null,
            ],
        ];
    }

    private function syncDistanceProfile(
        FleetCompany $company,
        array $distanceData
    ): void {
        if (
            !$company->default_terminal_id
        ) {
            return;
        }

        FleetDistanceProfile::query()
            ->updateOrCreate(
                [
                    'company_id' =>
                        $company->id,

                    'terminal_id' =>
                        $company
                            ->default_terminal_id,
                ],
                [
                    ...$distanceData,

                    'is_active' =>
                        $company->is_active,
                ]
            );
    }
}