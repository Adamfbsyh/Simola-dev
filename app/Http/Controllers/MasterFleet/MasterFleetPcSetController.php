<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetCompany;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetTerminal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterFleetPcSetController extends Controller
{
    /**
     * Menampilkan hasil grouping final PC 1 sampai PC 12.
     */
    public function index(Request $request): View
    {
        $operatorCount = (int) config(
            'master-fleet.operator_count',
            12
        );

        $filters = $request->validate([
            'pc' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . $operatorCount,
            ],

            'terminal_id' => [
                'nullable',
                'integer',
                'exists:fleet_terminals,id',
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:fleet_companies,id',
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in([
                    /*
                    * Status grouping baru.
                    */
                    'unchanged',
                    'moved',
                    'new_vehicle',
                    'manual',
                    'distance_missing',
                    'copied',

                    /*
                    * Status migrasi lama.
                    */
                    'matched',
                    'pc_changed',
                    'final_only',
                    'company_pending',
                    'company_unresolved',
                ]),
            ],

            'q' => [
                'nullable',
                'string',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    25,
                    50,
                    100,
                ]),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ambil periode grouping aktif
        |--------------------------------------------------------------------------
        */

        $period = FleetGroupingPeriod::query()
            ->withCount('assignments')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Belum ada periode published
        |--------------------------------------------------------------------------
        */

        if ($period === null) {
            return view(
                'master-fleet.pc-set.index',
                [
                    'period' => null,
                    'assignments' => null,
                    'pcCounts' => collect(),
                    'statistics' => [],
                    'terminals' => collect(),
                    'companies' => collect(),
                    'filters' => $filters,
                    'operatorCount' => $operatorCount,
                ]
            );
        }

        $periodAssignmentQuery =
            FleetGroupingAssignment::query()
                ->where(
                    'grouping_period_id',
                    $period->id
                );

        /*
        |--------------------------------------------------------------------------
        | Ringkasan jumlah kendaraan per PC
        |--------------------------------------------------------------------------
        */

        $rawPcCounts =
            FleetGroupingAssignment::query()
                ->select([
                    'pc_final',
                    DB::raw(
                        'COUNT(*) AS total'
                    ),
                ])
                ->where(
                    'grouping_period_id',
                    $period->id
                )
                ->groupBy('pc_final')
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
        | Statistik periode aktif
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total' =>
                (clone $periodAssignmentQuery)
                    ->count(),

            'matched' =>
                (clone $periodAssignmentQuery)
                    ->where(
                        'validation_status',
                        'matched'
                    )
                    ->count(),

            'pc_changed' =>
                (clone $periodAssignmentQuery)
                    ->where(
                        'validation_status',
                        'pc_changed'
                    )
                    ->count(),

            'final_only' =>
                (clone $periodAssignmentQuery)
                    ->where(
                        'validation_status',
                        'final_only'
                    )
                    ->count(),

            'company_pending' =>
                (clone $periodAssignmentQuery)
                    ->whereIn(
                        'validation_status',
                        [
                            'company_pending',
                            'company_unresolved',
                        ]
                    )
                    ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Daftar TLPG untuk pilihan filter
        |--------------------------------------------------------------------------
        */

        $terminalIds =
            (clone $periodAssignmentQuery)
                ->whereNotNull(
                    'terminal_id'
                )
                ->distinct()
                ->pluck(
                    'terminal_id'
                );

        $terminals =
            FleetTerminal::query()
                ->whereIn(
                    'id',
                    $terminalIds
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Daftar perusahaan untuk pilihan filter
        |--------------------------------------------------------------------------
        */

        $companyIds =
            (clone $periodAssignmentQuery)
                ->whereNotNull(
                    'company_id'
                )
                ->distinct()
                ->pluck(
                    'company_id'
                );

        $companies =
            FleetCompany::query()
                ->whereIn(
                    'id',
                    $companyIds
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Query daftar kendaraan
        |--------------------------------------------------------------------------
        */

        $assignmentsQuery =
            FleetGroupingAssignment::query()
                ->with([
                    'vehicle:id,plate_number,normalized_plate_number,is_active',
                    'company:id,name',
                    'terminal:id,name',
                ])
                ->where(
                    'grouping_period_id',
                    $period->id
                );

        $this->applyFilters(
            $assignmentsQuery,
            $filters
        );

        $perPage =
            (int) (
                $filters['per_page']
                ?? 25
            );

        $assignments =
            $assignmentsQuery
                ->orderBy('pc_final')
                ->orderBy(
                    'plate_number_snapshot'
                )
                ->paginate(
                    $perPage
                )
                ->withQueryString();

        return view(
            'master-fleet.pc-set.index',
            compact(
                'period',
                'assignments',
                'pcCounts',
                'statistics',
                'terminals',
                'companies',
                'filters',
                'operatorCount'
            )
        );
    }

    /**
     * Menerapkan filter pencarian.
     */
    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Filter PC final
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['pc']
            )
            &&
            $filters['pc'] !== ''
        ) {
            $query->where(
                'pc_final',
                (int) $filters['pc']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter TLPG
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['terminal_id']
            )
            &&
            $filters['terminal_id'] !== ''
        ) {
            $query->where(
                'terminal_id',
                (int) $filters[
                    'terminal_id'
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter perusahaan
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['company_id']
            )
            &&
            $filters['company_id'] !== ''
        ) {
            $query->where(
                'company_id',
                (int) $filters[
                    'company_id'
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter status validasi
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $filters['status']
            )
            &&
            $filters['status'] !== ''
        ) {
            $query->where(
                'validation_status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pencarian nopol, perusahaan, dan TLPG
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) (
                    $filters['q']
                    ?? ''
                )
            );

        if ($search !== '') {
            $query->where(
                function (
                    Builder $subQuery
                ) use (
                    $search
                ): void {
                    $subQuery
                        ->where(
                            'plate_number_snapshot',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'company_name_snapshot',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'terminal_name_snapshot',
                            'like',
                            '%' . $search . '%'
                        );
                }
            );
        }
    }
}