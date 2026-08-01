<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetCompany;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetTerminal;
use App\Models\FleetVehicle;
use App\Services\MasterFleet\FleetGroupingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class MasterFleetGroupingController extends Controller
{
    /**
     * Menampilkan Draft Grouping dan kendaraan
     * yang belum masuk ke draft.
     */
    public function index(
        Request $request
    ): View {
        $operatorCount = max(
            1,
            min(
                50,
                (int) (
                    $draft?->operator_count
                    ??
                    $published?->operator_count
                    ??
                    config(
                        'master-fleet.operator_count',
                        12
                    )
                )
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Tab aktif
        |--------------------------------------------------------------------------
        */

        $activeTab =
            $request->query('tab') === 'ungrouped'
                ? 'ungrouped'
                : 'draft';

        /*
        |--------------------------------------------------------------------------
        | PC Set Utama aktif
        |--------------------------------------------------------------------------
        */

        $published =
            FleetGroupingPeriod::query()
                ->where(
                    'status',
                    'published'
                )
                ->withCount(
                    'assignments'
                )
                ->orderByDesc(
                    'published_at'
                )
                ->orderByDesc('id')
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Draft grouping aktif
        |--------------------------------------------------------------------------
        */

        $draft =
            FleetGroupingPeriod::query()
                ->where(
                    'status',
                    'draft'
                )
                ->withCount(
                    'assignments'
                )
                ->orderByDesc('id')
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Nilai awal untuk view
        |--------------------------------------------------------------------------
        */

        $assignments = null;

        $ungroupedVehicles = null;

        $ungroupedCount = 0;

        $pcCounts =
            collect(
                range(
                    1,
                    $operatorCount
                )
            )->mapWithKeys(
                fn (int $pc): array => [
                    $pc => 0,
                ]
            );

        $statistics = [
            'total' => 0,
            'unchanged' => 0,
            'moved' => 0,
            'new_vehicle' => 0,
            'manual' => 0,
            'distance_missing' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Data hanya dihitung apabila draft tersedia
        |--------------------------------------------------------------------------
        */

        if ($draft !== null) {
            $baseQuery =
                FleetGroupingAssignment::query()
                    ->where(
                        'grouping_period_id',
                        $draft->id
                    );

            /*
            |--------------------------------------------------------------------------
            | Jumlah kendaraan per PC Final
            |--------------------------------------------------------------------------
            */

            $rawPcCounts =
                (clone $baseQuery)
                    ->select([
                        'pc_final',

                        DB::raw(
                            'COUNT(*) AS total'
                        ),
                    ])
                    ->whereNotNull(
                        'pc_final'
                    )
                    ->groupBy(
                        'pc_final'
                    )
                    ->pluck(
                        'total',
                        'pc_final'
                    );

            $pcCounts =
                collect(
                    range(
                        1,
                        $operatorCount
                    )
                )->mapWithKeys(
                    function (
                        int $pc
                    ) use (
                        $rawPcCounts
                    ): array {
                        return [
                            $pc =>
                                (int) (
                                    $rawPcCounts[$pc]
                                    ?? 0
                                ),
                        ];
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Statistik draft
            |--------------------------------------------------------------------------
            */

            $statistics = [
                'total' =>
                    (clone $baseQuery)
                        ->count(),

                'unchanged' =>
                    (clone $baseQuery)
                        ->whereNotNull(
                            'pc_initial'
                        )
                        ->whereColumn(
                            'pc_initial',
                            'pc_final'
                        )
                        ->count(),

                'moved' =>
                    (clone $baseQuery)
                        ->whereNotNull(
                            'pc_initial'
                        )
                        ->whereNotNull(
                            'pc_final'
                        )
                        ->whereColumn(
                            'pc_initial',
                            '!=',
                            'pc_final'
                        )
                        ->count(),

                'new_vehicle' =>
                    (clone $baseQuery)
                        ->whereNull(
                            'pc_initial'
                        )
                        ->count(),

                'manual' =>
                    (clone $baseQuery)
                        ->where(
                            'assignment_source',
                            'manual'
                        )
                        ->count(),

                'distance_missing' =>
                    (clone $baseQuery)
                        ->whereNull(
                            'distance_km'
                        )
                        ->count(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Tabel Data Draft
            |--------------------------------------------------------------------------
            */

            if ($activeTab === 'draft') {
                $assignmentsQuery =
                    FleetGroupingAssignment::query()
                        ->with([
                            'vehicle:id,plate_number,is_active',
                            'company:id,name',
                            'terminal:id,name',
                        ])
                        ->where(
                            'grouping_period_id',
                            $draft->id
                        );

                $search =
                    trim(
                        (string) $request
                            ->query(
                                'q',
                                ''
                            )
                    );

                if ($search !== '') {
                    $assignmentsQuery->where(
                        function (
                            $query
                        ) use (
                            $search
                        ): void {
                            $query
                                ->where(
                                    'plate_number_snapshot',
                                    'like',
                                    '%'
                                    .
                                    $search
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'company_name_snapshot',
                                    'like',
                                    '%'
                                    .
                                    $search
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'terminal_name_snapshot',
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

                $selectedPc =
                    $request->integer(
                        'pc'
                    );

                if (
                    $selectedPc >= 1
                    &&
                    $selectedPc <= $operatorCount
                ) {
                    $assignmentsQuery->where(
                        'pc_final',
                        $selectedPc
                    );
                }

                $assignments =
                    $assignmentsQuery
                        ->orderBy(
                            'pc_final'
                        )
                        ->orderBy(
                            'plate_number_snapshot'
                        )
                        ->paginate(
                            25,
                            ['*'],
                            'draft_page'
                        )
                        ->withQueryString();
            }

            /*
            |--------------------------------------------------------------------------
            | Kendaraan aktif yang belum masuk draft
            |--------------------------------------------------------------------------
            */

            $vehicleIdsInDraft =
                FleetGroupingAssignment::query()
                    ->select(
                        'vehicle_id'
                    )
                    ->where(
                        'grouping_period_id',
                        $draft->id
                    )
                    ->whereNotNull(
                        'vehicle_id'
                    );

            $ungroupedQuery =
                FleetVehicle::query()
                    ->with([
                        'company:id,name,default_terminal_id',
                    ])
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNotIn(
                        'id',
                        $vehicleIdsInDraft
                    );

            $ungroupedCount =
                (clone $ungroupedQuery)
                    ->count();

            /*
            |--------------------------------------------------------------------------
            | Tabel Belum Tergrouping
            |--------------------------------------------------------------------------
            */

            if (
                $activeTab
                ===
                'ungrouped'
            ) {
                $ungroupedSearch =
                    trim(
                        (string) $request
                            ->query(
                                'q',
                                ''
                            )
                    );

                if (
                    $ungroupedSearch
                    !==
                    ''
                ) {
                    $ungroupedQuery->where(
                        function (
                            $query
                        ) use (
                            $ungroupedSearch
                        ): void {
                            $query
                                ->where(
                                    'plate_number',
                                    'like',
                                    '%'
                                    .
                                    $ungroupedSearch
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'normalized_plate_number',
                                    'like',
                                    '%'
                                    .
                                    FleetVehicle::normalizePlateNumber(
                                        $ungroupedSearch
                                    )
                                    .
                                    '%'
                                )
                                ->orWhereHas(
                                    'company',
                                    function (
                                        $companyQuery
                                    ) use (
                                        $ungroupedSearch
                                    ): void {
                                        $companyQuery
                                            ->where(
                                                'name',
                                                'like',
                                                '%'
                                                .
                                                $ungroupedSearch
                                                .
                                                '%'
                                            );
                                    }
                                );
                        }
                    );
                }

                $ungroupedVehicles =
                    $ungroupedQuery
                        ->orderBy(
                            'plate_number'
                        )
                        ->paginate(
                            25,
                            ['*'],
                            'ungrouped_page'
                        )
                        ->withQueryString();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pilihan perusahaan aktif
        |--------------------------------------------------------------------------
        */

        $companies =
            FleetCompany::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get([
                    'id',
                    'name',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Pilihan TLPG aktif
        |--------------------------------------------------------------------------
        */

        $terminals =
            FleetTerminal::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get([
                    'id',
                    'name',
                ]);

        return view(
            'master-fleet.grouping.index',
            compact(
                'published',
                'draft',
                'assignments',
                'pcCounts',
                'statistics',
                'companies',
                'terminals',
                'operatorCount',
                'activeTab',
                'ungroupedVehicles',
                'ungroupedCount'
            )
        );
    }

    /**
     * Membuat draft baru dari PC Set Utama.
     */
    public function createDraft(
        Request $request,
        FleetGroupingService $service
    ): RedirectResponse {
        $validated =
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'effective_date' => [
                    'required',
                    'date',
                ],

                'operator_count' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:50',
                ],
            ]);

        try {
            $service->createDraft(
                userId:
                    (int) $request
                        ->user()
                        ->id,

                name:
                    trim(
                        $validated['name']
                    ),

                effectiveDate:
                    $validated['effective_date'],

                operatorCount:
                    (int) $validated['operator_count']
            );

            return redirect()
                ->route(
                    'master-fleet.grouping.index',
                    [
                        'tab' => 'draft',
                    ]
                )
                ->with(
                    'success',
                    'Draft grouping berhasil dibuat.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    app()->isLocal()
                        ? $e->getMessage()
                        : 'Draft grouping gagal dibuat.'
                );
        }
    }

    /**
     * Menghitung profil jarak tanpa mengubah pembagian PC.
     */
    public function calculateDistances(
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        try {
            $result =
                $service->calculateDistances(
                    $period
                );

            $message =
                'Profil jarak selesai dihitung. '
                .
                $result['filled']
                .
                ' dari '
                .
                $result['total']
                .
                ' kendaraan memiliki jarak.';

            if ($result['missing'] > 0) {
                $message .=
                    ' Masih ada '
                    .
                    $result['missing']
                    .
                    ' kendaraan yang koordinat atau pasangan datanya belum lengkap.';
            }

            return redirect()
                ->route(
                    'master-fleet.grouping.index',
                    [
                        'tab' => 'draft',
                    ]
                )
                ->with(
                    'success',
                    $message
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $e->getMessage()
                    : 'Profil jarak gagal dihitung.'
            );
        }
    }

    /**
     * Generate PC Final berdasarkan jarak dan bobot.
     */
    public function generate(
        Request $request,
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        try {
            $result =
                $service->generate(
                    period:
                        $period,

                    preserveManual:
                        $request->boolean(
                            'preserve_manual'
                        )
                );

            return redirect()
                ->route(
                    'master-fleet.grouping.index',
                    [
                        'tab' => 'draft',
                    ]
                )
                ->with(
                    'success',
                    'Generate selesai. '
                    .
                    $result['generated']
                    .
                    ' kendaraan dihitung, '
                    .
                    $result['manual_preserved']
                    .
                    ' edit manual dipertahankan.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $e->getMessage()
                    : 'Generate grouping gagal.'
            );
        }
    }

    /**
     * Memperbarui PC Final secara manual.
     */
    public function updateAssignment(
        Request $request,
        FleetGroupingPeriod $period,
        FleetGroupingAssignment $assignment,
        FleetGroupingService $service
    ): RedirectResponse {
        $operatorCount = max(
            1,
            (int) config(
                'master-fleet.operator_count',
                12
            )
        );

        $validated =
            $request->validate([
                'pc_final' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:' . $operatorCount,
                ],

                'note' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $service->updateManualPc(
                period:
                    $period,

                assignment:
                    $assignment,

                pcFinal:
                    (int) $validated[
                        'pc_final'
                    ],

                userId:
                    (int) $request
                        ->user()
                        ->id,

                note:
                    $validated[
                        'note'
                    ]
                    ?? null
            );

            return back()->with(
                'success',
                'PC Final berhasil diubah secara manual.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    app()->isLocal()
                        ? $e->getMessage()
                        : 'PC Final gagal diperbarui.'
                );
        }
    }

    /**
     * Menambahkan kendaraan ke dalam draft.
     */
    public function addVehicle(
        Request $request,
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        $operatorCount = max(
            1,
            (int) config(
                'master-fleet.operator_count',
                12
            )
        );

        $validated =
            $request->validate([
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

                'terminal_id' => [
                    'required',
                    'integer',
                    'exists:fleet_terminals,id',
                ],

                'pc_final' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:' . $operatorCount,
                ],

                'note' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        try {
            $service->addVehicle(
                period:
                    $period,

                data:
                    $validated,

                userId:
                    (int) $request
                        ->user()
                        ->id
            );

            return back()->with(
                'success',
                'Nopol berhasil dimasukkan ke Draft Grouping.'
            );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    app()->isLocal()
                        ? $e->getMessage()
                        : 'Nopol gagal dimasukkan ke draft.'
                );
        }
    }

    /**
     * Mengubah jumlah PC pada Draft Grouping.
     */
    public function updateOperatorCount(
        Request $request,
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        $validated =
            $request->validate([
                'operator_count' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:50',
                ],
            ]);

        try {
            $updatedPeriod =
                $service->updateOperatorCount(
                    period: $period,
                    operatorCount:
                        (int) $validated[
                            'operator_count'
                        ]
                );

            return redirect()
                ->route(
                    'master-fleet.grouping.index',
                    [
                        'tab' => 'draft',
                    ]
                )
                ->with(
                    'success',
                    'Jumlah PC berhasil diubah menjadi '
                    .
                    $updatedPeriod->operator_count
                    .
                    '. Jalankan Generate PC Final agar pembagian '
                    . 'menyesuaikan jumlah PC terbaru.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    app()->isLocal()
                        ? $e->getMessage()
                        : 'Jumlah PC gagal diperbarui.'
                );
        }
    }

    /**
     * Mengembalikan draft ke kondisi PC Set Utama aktif.
     */
    public function reset(
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        try {
            $result =
                $service->resetDraft(
                    $period
                );

            return redirect()
                ->route(
                    'master-fleet.grouping.index',
                    [
                        'tab' => 'draft',
                    ]
                )
                ->with(
                    'success',
                    'Draft berhasil direset. '
                    .
                    $result['copied']
                    .
                    ' kendaraan disalin kembali dari PC Set Utama.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $e->getMessage()
                    : 'Draft gagal direset.'
            );
        }
    }

    /**
     * Mempublikasikan PC Final menjadi PC Set Utama.
     */
    public function publish(
        Request $request,
        FleetGroupingPeriod $period,
        FleetGroupingService $service
    ): RedirectResponse {
        try {
            $published =
                $service->publish(
                    period:
                        $period,

                    userId:
                        (int) $request
                            ->user()
                            ->id
                );

            return redirect()
                ->route(
                    'master-fleet.pc-set.index'
                )
                ->with(
                    'success',
                    'Grouping '
                    .
                    $published->name
                    .
                    ' berhasil dipublikasikan sebagai PC Set Utama.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $e->getMessage()
                    : 'Grouping gagal dipublikasikan.'
            );
        }
    }
}