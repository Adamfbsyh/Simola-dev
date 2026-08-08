<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateEvidenceFoldersJob;
use App\Models\FleetGoogleAccount;
use App\Models\FleetGoogleK302DailyFile;
use App\Models\FleetGoogleSyncLog;
use App\Models\FleetGroupingPeriod;
use App\Services\MasterFleet\MasterFleetGoogleWorkspaceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class MasterFleetGoogleWorkspaceController extends Controller
{
    public function __construct(
        private readonly
        MasterFleetGoogleWorkspaceService $service
    ) {
    }

    public function index(
        Request $request
    ): View {
        $period = FleetGroupingPeriod::query()
            ->where(
                'status',
                'published'
            )
            ->orderByDesc(
                'published_at'
            )
            ->orderByDesc(
                'id'
            )
            ->withCount(
                'assignments'
            )
            ->first();

        $accounts = FleetGoogleAccount::query()
            ->where(
                'user_id',
                $request->user()->id
            )
            ->get()
            ->keyBy(
                'purpose'
            );

        $k302Account = $accounts->get(
            FleetGoogleAccount::PURPOSE_K302
        );

        $evidenceAccount = $accounts->get(
            FleetGoogleAccount::PURPOSE_EVIDENCE
        );

        $spreadsheetLogTypes = [
            'pc_set_spreadsheet',
            'k302_daily_spreadsheet',
        ];

        $spreadsheetLogs = FleetGoogleSyncLog::query()
            ->with([
                'createdBy:id,name',
            ])
            ->whereIn(
                'sync_type',
                $spreadsheetLogTypes
            )
            ->latest(
                'id'
            )
            ->limit(8)
            ->get();

        $evidenceLogs = FleetGoogleSyncLog::query()
            ->with([
                'createdBy:id,name',
            ])
            ->where(
                'sync_type',
                'evidence_folders'
            )
            ->latest(
                'id'
            )
            ->limit(8)
            ->get();

        $historySummaryQuery =
            FleetGoogleSyncLog::query();

        $historySummary = [
            'today' =>
                (clone $historySummaryQuery)
                    ->whereDate(
                        'created_at',
                        today()
                    )
                    ->count(),

            'running' =>
                (clone $historySummaryQuery)
                    ->where(
                        'status',
                        'running'
                    )
                    ->count(),

            'success_today' =>
                (clone $historySummaryQuery)
                    ->whereDate(
                        'created_at',
                        today()
                    )
                    ->where(
                        'status',
                        'success'
                    )
                    ->count(),

            'attention_today' =>
                (clone $historySummaryQuery)
                    ->whereDate(
                        'created_at',
                        today()
                    )
                    ->whereIn(
                        'status',
                        [
                            'failed',
                            'partial',
                        ]
                    )
                    ->count(),
        ];

        $k302DailyFiles =
            FleetGoogleK302DailyFile::query()
                ->latest(
                    'workspace_date'
                )
                ->limit(12)
                ->get();

        $statistics = [
            'total' =>
                0,

            'p1' =>
                0,

            'p2' =>
                0,
        ];

        if ($period !== null) {
            $assignmentQuery =
                $period
                    ->assignments()
                    ->whereNotNull(
                        'pc_final'
                    );

            $statistics = [
                'total' =>
                    (clone $assignmentQuery)
                        ->count(),

                'p1' =>
                    (clone $assignmentQuery)
                        ->where(
                            'operational_type',
                            'P1'
                        )
                        ->count(),

                'p2' =>
                    (clone $assignmentQuery)
                        ->where(
                            'operational_type',
                            'P2'
                        )
                        ->count(),
            ];
        }

        $spreadsheetId = (string) config(
            'services.google_workspace.source_spreadsheet_id'
        );

        $evidenceRootFolderId = (string) config(
            'services.google_workspace.evidence_root_folder_id'
        );

        $k302RootFolderId = (string) config(
            'services.google_workspace.k302_root_folder_id'
        );

        return view(
            'master-fleet.google-workspace.index',
            [
                'period' =>
                    $period,

                'k302Account' =>
                    $k302Account,

                'evidenceAccount' =>
                    $evidenceAccount,

                'spreadsheetLogs' =>
                    $spreadsheetLogs,

                'evidenceLogs' =>
                    $evidenceLogs,

                'historySummary' =>
                    $historySummary,

                'k302DailyFiles' =>
                    $k302DailyFiles,

                'statistics' =>
                    $statistics,

                'spreadsheetId' =>
                    $spreadsheetId,

                'spreadsheetUrl' =>
                    $spreadsheetId !== ''
                        ? 'https://docs.google.com/spreadsheets/d/'
                            . $spreadsheetId
                            . '/edit'
                        : null,

                'sourceSheetName' =>
                    config(
                        'services.google_workspace.source_sheet_name'
                    ),

                'evidenceFolderUrl' =>
                    $evidenceRootFolderId !== ''
                        ? 'https://drive.google.com/drive/folders/'
                            . $evidenceRootFolderId
                        : null,

                'k302FolderUrl' =>
                    $k302RootFolderId !== ''
                        ? 'https://drive.google.com/drive/folders/'
                            . $k302RootFolderId
                        : null,

                'k302TemplateUrl' =>
                    ($templateId = trim((string) config(
                        'services.google_workspace.k302_template_spreadsheet_id'
                    ))) !== ''
                        ? 'https://docs.google.com/spreadsheets/d/'
                            . $templateId
                            . '/edit'
                        : null,

                'k302NopolStartCell' =>
                    (string) config(
                        'services.google_workspace.k302_nopol_start_cell',
                        'C25'
                    ),

                'k302TlpgStartCell' =>
                    (string) config(
                        'services.google_workspace.k302_tlpg_start_cell',
                        'F25'
                    ),

                'k302PcStartCell' =>
                    (string) config(
                        'services.google_workspace.k302_pc_start_cell',
                        'AE25'
                    ),

                'k302NopolFormula' =>
                    $this->buildImportRangeFormula(
                        $spreadsheetId,
                        3
                    ),

                'k302TlpgFormula' =>
                    $this->buildImportRangeFormula(
                        $spreadsheetId,
                        4
                    ),

                'k302PcFormula' =>
                    $this->buildImportRangeFormula(
                        $spreadsheetId,
                        2
                    ),
            ]
        );
    }

    public function connect(
        Request $request,
        string $purpose
    ): RedirectResponse {
        try {
            $purpose = $this->service
                ->normalizePurpose(
                    $purpose
                );
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'error',
                $exception->getMessage()
            );
        }

        $state = Str::random(
            64
        );

        $request
            ->session()
            ->put(
                'google_workspace_oauth',
                [
                    'state' =>
                        $state,

                    'purpose' =>
                        $purpose,
                ]
            );

        return redirect()->away(
            $this->service
                ->authorizationUrl(
                    $state
                )
        );
    }

    public function callback(
        Request $request
    ): RedirectResponse {
        $request->validate([
            'code' => [
                'required',
                'string',
            ],

            'state' => [
                'required',
                'string',
            ],
        ]);

        $oauthSession =
            $request
                ->session()
                ->pull(
                    'google_workspace_oauth'
                );

        $expectedState = is_array($oauthSession)
            ? ($oauthSession['state'] ?? null)
            : null;

        $purpose = is_array($oauthSession)
            ? ($oauthSession['purpose'] ?? null)
            : null;

        if (
            !is_string($expectedState)
            || !is_string($purpose)
            || !hash_equals(
                $expectedState,
                (string) $request->input(
                    'state'
                )
            )
        ) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'error',
                'State OAuth tidak valid atau sudah kedaluwarsa.'
            );
        }

        try {
            $account =
                $this->service
                    ->connect(
                        $request->user(),
                        (string) $request->input(
                            'code'
                        ),
                        $purpose
                    );

            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'success',
                'Akun Google '
                . ($account->google_email ?: '')
                . ' berhasil dihubungkan untuk '
                . $account->purposeLabel()
                . '.'
            );
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'error',
                $exception->getMessage()
            );
        }
    }

    public function disconnect(
        Request $request,
        string $purpose
    ): RedirectResponse {
        try {
            $purpose = $this->service
                ->normalizePurpose(
                    $purpose
                );

            $this->service
                ->disconnect(
                    $request->user(),
                    $purpose
                );

            $label = $purpose
                === FleetGoogleAccount::PURPOSE_K302
                    ? 'K3-02'
                    : 'Evidence';

            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'success',
                'Koneksi akun Google '
                . $label
                . ' telah diputus.'
            );
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'error',
                $exception->getMessage()
            );
        }
    }

    public function syncSpreadsheet(
        Request $request
    ): RedirectResponse {
        try {
            $log =
                $this->service
                    ->syncPcSet(
                        $request->user()
                    );

            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'success',
                $log->message
                . ' Total kendaraan: '
                . $log->updated_items
                . '.'
            );
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'error',
                $this->compactErrorMessage(
                    $exception,
                    'Sinkronisasi PC Set ke Google Spreadsheet gagal.'
                )
            );
        }
    }

    public function generateK302Daily(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'generation_mode' => [
                'nullable',
                'in:daily,weekly,monthly,range',
            ],

            'workspace_date' => [
                'nullable',
                'required_if:generation_mode,daily,weekly',
                'date_format:Y-m-d',
            ],

            'workspace_month' => [
                'nullable',
                'required_if:generation_mode,monthly',
                'date_format:Y-m',
            ],

            'start_date' => [
                'nullable',
                'required_if:generation_mode,range',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'nullable',
                'required_if:generation_mode,range',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ]);

        try {
            $mode = (string) (
                $validated['generation_mode']
                ?? 'daily'
            );

            [$startDate, $endDate] = match ($mode) {
                'weekly' =>
                    $this->resolveWeeklyK302Range(
                        (string) $validated['workspace_date']
                    ),

                'monthly' =>
                    $this->resolveMonthlyK302Range(
                        (string) $validated['workspace_month']
                    ),

                'range' => [
                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['start_date']
                    )->startOfDay(),

                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['end_date']
                    )->startOfDay(),
                ],

                default => [
                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['workspace_date']
                    )->startOfDay(),

                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['workspace_date']
                    )->startOfDay(),
                ],
            };

            $dayCount = $startDate
                ->copy()
                ->startOfDay()
                ->diffInDays(
                    $endDate->copy()->startOfDay()
                ) + 1;

            if ($dayCount > 62) {
                return to_route(
                    'master-fleet.google-workspace.index'
                )->withInput()->with(
                    'error',
                    'Rentang maksimal adalah 62 hari dalam satu proses.'
                );
            }

            $result = $this->service
                ->generateK302SpreadsheetBatch(
                    $request->user(),
                    $startDate,
                    $endDate
                );

            $redirect = to_route(
                'master-fleet.google-workspace.index'
            )->with(
                $result['failed'] > 0
                    ? 'warning'
                    : 'success',
                $result['message']
                    . ' Rentang: '
                    . $startDate->format('d-m-Y')
                    . ' s.d. '
                    . $endDate->format('d-m-Y')
                    . '.'
            );

            if (
                is_string($result['first_url'])
                && $result['first_url'] !== ''
            ) {
                $redirect->with(
                    'generated_k302_url',
                    $result['first_url']
                );
            }

            if ($result['errors'] !== []) {
                $redirect->with(
                    'k302_batch_errors',
                    array_slice(
                        $result['errors'],
                        0,
                        10
                    )
                );
            }

            return $redirect;
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->withInput()->with(
                'error',
                $this->compactErrorMessage(
                    $exception
                )
            );
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveWeeklyK302Range(
        string $workspaceDate
    ): array {
        $selectedDate = Carbon::createFromFormat(
            'Y-m-d',
            $workspaceDate
        )->startOfDay();

        return [
            $selectedDate
                ->copy()
                ->startOfWeek(Carbon::MONDAY),

            $selectedDate
                ->copy()
                ->endOfWeek(Carbon::SUNDAY)
                ->startOfDay(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveMonthlyK302Range(
        string $workspaceMonth
    ): array {
        $selectedMonth = Carbon::createFromFormat(
            'Y-m',
            $workspaceMonth
        )->startOfMonth();

        return [
            $selectedMonth->copy(),
            $selectedMonth
                ->copy()
                ->endOfMonth()
                ->startOfDay(),
        ];
    }

    public function generateEvidence(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'evidence_generation_mode' => [
                'nullable',
                'in:daily,weekly,monthly,range',
            ],

            'evidence_workspace_date' => [
                'nullable',
                'required_if:evidence_generation_mode,daily,weekly',
                'date_format:Y-m-d',
            ],

            'evidence_workspace_month' => [
                'nullable',
                'required_if:evidence_generation_mode,monthly',
                'date_format:Y-m',
            ],

            'evidence_start_date' => [
                'nullable',
                'required_if:evidence_generation_mode,range',
                'date_format:Y-m-d',
            ],

            'evidence_end_date' => [
                'nullable',
                'required_if:evidence_generation_mode,range',
                'date_format:Y-m-d',
                'after_or_equal:evidence_start_date',
            ],
        ]);

        try {
            $mode = (string) (
                $validated['evidence_generation_mode']
                ?? 'daily'
            );

            [$startDate, $endDate] = match ($mode) {
                'weekly' =>
                    $this->resolveWeeklyK302Range(
                        (string) $validated['evidence_workspace_date']
                    ),

                'monthly' =>
                    $this->resolveMonthlyK302Range(
                        (string) $validated['evidence_workspace_month']
                    ),

                'range' => [
                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['evidence_start_date']
                    )->startOfDay(),

                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['evidence_end_date']
                    )->startOfDay(),
                ],

                default => [
                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['evidence_workspace_date']
                    )->startOfDay(),

                    Carbon::createFromFormat(
                        'Y-m-d',
                        (string) $validated['evidence_workspace_date']
                    )->startOfDay(),
                ],
            };

            $dayCount = $startDate
                ->copy()
                ->startOfDay()
                ->diffInDays(
                    $endDate->copy()->startOfDay()
                ) + 1;

            if ($dayCount > 62) {
                return to_route(
                    'master-fleet.google-workspace.index'
                )->withInput()->with(
                    'error',
                    'Rentang Evidence maksimal adalah 62 hari dalam satu proses.'
                );
            }

            $queued = 0;

            for (
                $date = $startDate->copy();
                $date->lte($endDate);
                $date->addDay()
            ) {
                GenerateEvidenceFoldersJob::dispatch(
                    $request->user()->id,
                    $date->toDateString()
                )
                    ->onConnection('database')
                    ->onQueue('evidence');

                $queued++;
            }

            return to_route(
                'master-fleet.google-workspace.index'
            )->with(
                'success',
                $queued
                . ' pekerjaan Folder Evidence sudah masuk antrean untuk '
                . $startDate->format('d-m-Y')
                . ' s.d. '
                . $endDate->format('d-m-Y')
                . '. Worker Windows akan memproses setiap tanggal secara berurutan. '
                . 'Pantau hasilnya pada Riwayat Proses.'
            );
        } catch (Throwable $exception) {
            return to_route(
                'master-fleet.google-workspace.index'
            )->withInput()->with(
                'error',
                $this->compactErrorMessage(
                    $exception,
                    'Pekerjaan Evidence gagal dimasukkan ke antrean.'
                )
            );
        }
    }

    private function compactErrorMessage(
        Throwable $exception,
        string $fallback
    ): string {
        $message = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $exception->getMessage()
            )
            ?? ''
        );

        if ($message === '') {
            $message = $fallback;
        }

        return Str::limit(
            $message,
            1200,
            '...'
        );
    }

    private function buildImportRangeFormula(
        string $spreadsheetId,
        int $column
    ): string {
        if ($spreadsheetId === '') {
            return '';
        }

        $sheetName = (string) config(
            'services.google_workspace.source_sheet_name',
            'SIMOLA_PC_SET'
        );

        return '=QUERY(IMPORTRANGE("'
            . $spreadsheetId
            . '";"'
            . $sheetName
            . '!A2:H");"select Col'
            . $column
            . ' where Col3 is not null";0)';
    }
}
