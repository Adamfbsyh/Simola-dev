<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\MasterFleetAudit;
use App\Support\MasterFleet\FleetType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterFleetAuditController extends Controller
{
    public function index(
        Request $request
    ): View {
        $filters =
            $this->filters(
                $request
            );

        $baseQuery =
            $this->filteredQuery(
                $filters
            );

        $summary = [
            'total' =>
                (clone $baseQuery)
                    ->count(),

            'vehicle' =>
                (clone $baseQuery)
                    ->where(
                        'module',
                        'Master Kendaraan'
                    )
                    ->count(),

            'grouping' =>
                (clone $baseQuery)
                    ->where(
                        'module',
                        'Draft Grouping'
                    )
                    ->count(),

            'master_data' =>
                (clone $baseQuery)
                    ->whereIn(
                        'module',
                        [
                            'Master Terminal',
                            'Master Perusahaan',
                        ]
                    )
                    ->count(),

            'import' =>
                (clone $baseQuery)
                    ->where(
                        'module',
                        'Import Master Fleet'
                    )
                    ->count(),
        ];

        $audits =
            (clone $baseQuery)
                ->orderByDesc(
                    'occurred_at'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(50)
                ->withQueryString();

        $modules =
            MasterFleetAudit::query()
                ->select(
                    'module'
                )
                ->distinct()
                ->orderBy(
                    'module'
                )
                ->pluck(
                    'module'
                );

        $actions =
            MasterFleetAudit::query()
                ->select(
                    'action'
                )
                ->distinct()
                ->orderBy(
                    'action'
                )
                ->pluck(
                    'action'
                );

        return view(
            'master-fleet.audit.index',
            [
                'audits' =>
                    $audits,

                'filters' =>
                    $filters,

                'summary' =>
                    $summary,

                'modules' =>
                    $modules,

                'actions' =>
                    $actions,

                'fleetTypes' => [
                    FleetType::LPG =>
                        'MT LPG',

                    FleetType::PERTASHOP =>
                        'MT PERTASHOP',
                ],
            ]
        );
    }

    public function export(
        Request $request
    ): BinaryFileResponse {
        $filters =
            $this->filters(
                $request
            );

        $rows =
            $this->filteredQuery(
                $filters
            )
                ->orderBy(
                    'occurred_at'
                )
                ->orderBy(
                    'id'
                )
                ->limit(10000)
                ->get();

        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $sheet->setTitle(
            'Riwayat Perubahan'
        );

        $headers = [
            'Waktu',
            'Jenis Armada',
            'Modul',
            'Aksi',
            'Data',
            'Deskripsi',
            'Sebelum',
            'Sesudah',
            'Oleh',
            'Email',
            'Route',
            'IP',
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        $rowNumber = 2;

        foreach (
            $rows
            as $audit
        ) {
            $sheet->fromArray(
                [
                    optional(
                        $audit->occurred_at
                    )
                        ->timezone(
                            config(
                                'app.timezone',
                                'Asia/Jakarta'
                            )
                        )
                        ?->format(
                            'd/m/Y H:i:s'
                        ),

                    $this->fleetTypeLabel(
                        $audit->fleet_type
                    ),

                    $audit->module,
                    $audit->action,
                    $audit->subject_label,
                    $audit->description,

                    $this->jsonText(
                        $audit->before_data
                    ),

                    $this->jsonText(
                        $audit->after_data
                    ),

                    $audit->user_name,
                    $audit->user_email,
                    $audit->route_name,
                    $audit->ip_address,
                ],
                null,
                'A'
                .
                $rowNumber
            );

            $rowNumber++;
        }

        $sheet
            ->getStyle(
                'A1:L1'
            )
            ->getFont()
            ->setBold(
                true
            );

        $sheet->freezePane(
            'A2'
        );

        $sheet->setAutoFilter(
            'A1:L'
            .
            max(
                1,
                $rowNumber - 1
            )
        );

        foreach (
            range(
                'A',
                'L'
            )
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(
                    true
                );
        }

        foreach (
            [
                'G',
                'H',
            ]
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(
                    false
                )
                ->setWidth(
                    45
                );
        }

        $sheet
            ->getStyle(
                'A1:L'
                .
                max(
                    2,
                    $rowNumber - 1
                )
            )
            ->getAlignment()
            ->setVertical(
                'top'
            )
            ->setWrapText(
                true
            );

        $filename =
            'RIWAYAT_PERUBAHAN_MASTER_FLEET_'
            .
            str_replace(
                '-',
                '_',
                $filters[
                    'period'
                ]
            )
            .
            '.xlsx';

        $tmp =
            tempnam(
                sys_get_temp_dir(),
                'simola-audit-'
            );

        if ($tmp === false) {
            abort(
                500,
                'File sementara tidak dapat dibuat.'
            );
        }

        $writer =
            new Xlsx(
                $spreadsheet
            );

        $writer->save(
            $tmp
        );

        $spreadsheet
            ->disconnectWorksheets();

        return response()
            ->download(
                $tmp,
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(
                true
            );
    }

    private function filters(
        Request $request
    ): array {
        $period =
            trim(
                (string) $request->query(
                    'period',
                    now()->format(
                        'Y-m'
                    )
                )
            );

        if (
            !preg_match(
                '/^\d{4}-\d{2}$/',
                $period
            )
        ) {
            $period =
                now()->format(
                    'Y-m'
                );
        }

        return [
            'period' =>
                $period,

            'fleet_type' =>
                trim(
                    (string) $request
                        ->query(
                            'fleet_type',
                            ''
                        )
                ),

            'module' =>
                trim(
                    (string) $request
                        ->query(
                            'module',
                            ''
                        )
                ),

            'action' =>
                trim(
                    (string) $request
                        ->query(
                            'action',
                            ''
                        )
                ),

            'q' =>
                trim(
                    (string) $request
                        ->query(
                            'q',
                            ''
                        )
                ),
        ];
    }

    private function filteredQuery(
        array $filters
    ): Builder {
        $start =
            Carbon::createFromFormat(
                'Y-m',
                $filters[
                    'period'
                ]
            )
                ->startOfMonth();

        $end =
            $start
                ->copy()
                ->endOfMonth();

        return MasterFleetAudit::query()
            ->whereBetween(
                'occurred_at',
                [
                    $start,
                    $end,
                ]
            )
            ->when(
                $filters[
                    'fleet_type'
                ]
                !== '',
                fn (
                    Builder $query
                ) =>
                    $query->where(
                        'fleet_type',
                        $filters[
                            'fleet_type'
                        ]
                    )
            )
            ->when(
                $filters[
                    'module'
                ]
                !== '',
                fn (
                    Builder $query
                ) =>
                    $query->where(
                        'module',
                        $filters[
                            'module'
                        ]
                    )
            )
            ->when(
                $filters[
                    'action'
                ]
                !== '',
                fn (
                    Builder $query
                ) =>
                    $query->where(
                        'action',
                        $filters[
                            'action'
                        ]
                    )
            )
            ->when(
                $filters[
                    'q'
                ]
                !== '',
                function (
                    Builder $query
                ) use (
                    $filters
                ): void {
                    $search =
                        $filters[
                            'q'
                        ];

                    $query->where(
                        function (
                            Builder $nested
                        ) use (
                            $search
                        ): void {
                            $nested
                                ->where(
                                    'subject_label',
                                    'like',
                                    '%'
                                    .
                                    $search
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    '%'
                                    .
                                    $search
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'user_name',
                                    'like',
                                    '%'
                                    .
                                    $search
                                    .
                                    '%'
                                )
                                ->orWhere(
                                    'user_email',
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

    private function fleetTypeLabel(
        ?string $value
    ): string {
        return match ($value) {
            FleetType::LPG =>
                'MT LPG',

            FleetType::PERTASHOP =>
                'MT PERTASHOP',

            'SHARED' =>
                'REFERENSI BERSAMA',

            default =>
                $value
                ?: '—',
        };
    }

    private function jsonText(
        ?array $value
    ): string {
        if (
            !$value
        ) {
            return '';
        }

        return (string) json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
            |
            JSON_PRETTY_PRINT
        );
    }
}
