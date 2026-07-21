<?php

namespace App\Http\Controllers;

use App\Models\K32DailyRecord;
use App\Services\K32DailyService;
use App\Services\PelanggaranCategoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class K32ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Halaman filter laporan
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        K32DailyService $dailyService
    ) {
        $latestDateValue =
            K32DailyRecord::query()
                ->whereNotNull(
                    'source_date'
                )
                ->max(
                    'source_date'
                );

        $latestDate =
            $latestDateValue
                ? Carbon::parse(
                    $latestDateValue
                )
                : now();

        /*
         * Ambil pilihan TLPG langsung dari sheet.
         * Jika Google Sheet sedang tidak dapat dibaca,
         * gunakan data TLPG yang sudah tersimpan.
         */
        try {
            $sheetRows =
                $dailyService->getReportRows(
                    $latestDate
                        ->copy()
                        ->startOfMonth(),

                    $latestDate
                        ->copy()
                        ->endOfMonth()
                );

            $tlpgOptions = collect(
                $sheetRows['vehicles']
                ?? []
            )
                ->pluck('tlpg')
                ->filter()
                ->unique()
                ->sort(
                    SORT_NATURAL |
                    SORT_FLAG_CASE
                )
                ->values();
        } catch (\Throwable $e) {
            report($e);

            $tlpgOptions =
                K32DailyRecord::query()
                    ->whereNotNull('tlpg')
                    ->where('tlpg', '!=', '')
                    ->distinct()
                    ->orderBy('tlpg')
                    ->pluck('tlpg');
        }

        return view(
            'k32-report.index',
            [
                'tlpgOptions' =>
                    $tlpgOptions,

                'defaultDate' =>
                    $latestDate
                        ->format('Y-m-d'),

                'defaultMonth' =>
                    $latestDate
                        ->format('Y-m'),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Preview HTML
    |--------------------------------------------------------------------------
    */
    public function preview(
        Request $request,
        K32DailyService $dailyService,
        PelanggaranCategoryService $categoryService
    ) {
        $validated =
            $this->validateReportRequest(
                $request
            );

        $report =
            $this->buildReportData(
                $validated,
                $dailyService,
                $categoryService
            );

        return view(
            'k32-report.report',
            [
                ...$report,

                'pdfMode' =>
                    false,

                'downloadUrl' =>
                    route(
                        'k32-report.pdf',
                        array_merge(
                            $validated,
                            [
                                'output' =>
                                    'download',
                            ]
                        )
                    ),

                'streamUrl' =>
                    route(
                        'k32-report.pdf',
                        array_merge(
                            $validated,
                            [
                                'output' =>
                                    'stream',
                            ]
                        )
                    ),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generate PDF
    |--------------------------------------------------------------------------
    */
    public function pdf(
        Request $request,
        K32DailyService $dailyService,
        PelanggaranCategoryService $categoryService
    ) {
        $validated =
            $this->validateReportRequest(
                $request
            );

        $report =
            $this->buildReportData(
                $validated,
                $dailyService,
                $categoryService
            );
        
        $logoPertamina = $this->imageToDataUri(public_path('images/pertamina-patra-logistik.png'));
        $logoK3        = $this->imageToDataUri(public_path('images/logo-k3.png'));

        $signatureImages = [
            'team_lead_1' => $this->imageToDataUri(public_path('images/signatures/team_lead_1.png')),
            'team_lead_2' => $this->imageToDataUri(public_path('images/signatures/team_lead_2.png')),
            'team_lead_3' => $this->imageToDataUri(public_path('images/signatures/team_lead_3.png')),
            'team_lead_4' => $this->imageToDataUri(public_path('images/signatures/team_lead_4.png')),
            'spv_rtc'     => $this->imageToDataUri(public_path('images/signatures/spv_rtc.png')),
        ];

        $pdf = Pdf::loadView(
            'k32-report.report',
            [
                ...$report,

                'pdfMode' =>
                    true,

                'downloadUrl' =>
                    null,

                'streamUrl' =>
                    null,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Ukuran khusus: lebar A3, tinggi A2
        |--------------------------------------------------------------------------
        |
        | Lebar  : 297 mm
        | Tinggi : 594 mm
        |
        | Ini membuat satu TLPG tetap satu halaman tanpa membuat tabel melebar.
        |
        */

        $paperWidth = 297 * 72 / 25.4;
        $paperHeight = 594 * 72 / 25.4;

        $pdf->setPaper([
            0,
            0,
            $paperWidth,
            $paperHeight,
        ]);
        $fileName =
            $this->buildFileName(
                $report['mode'],
                $report['periodFileName'],
                $report['selectedTlpg']
            );

        if (
            $request->input('output')
            === 'stream'
        ) {
            return $pdf->stream(
                $fileName
            );
        }

        return $pdf->download(
            $fileName
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi
    |--------------------------------------------------------------------------
    */
    private function validateReportRequest(
        Request $request
    ): array {
        return $request->validate(
            [
                'mode' => [
                    'required',
                    'in:daily,monthly',
                ],

                'date' => [
                    'nullable',
                    'date',
                    'required_if:mode,daily',
                ],

                'month' => [
                    'nullable',
                    'date_format:Y-m',
                    'required_if:mode,monthly',
                ],

                'tlpg' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
            ],
            [
                'mode.required' =>
                    'Pilih jenis laporan.',

                'date.required_if' =>
                    'Pilih tanggal laporan harian.',

                'month.required_if' =>
                    'Pilih bulan laporan bulanan.',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Membentuk data laporan
    |--------------------------------------------------------------------------
    */
    private function buildReportData(
        array $validated,
        K32DailyService $dailyService,
        PelanggaranCategoryService $categoryService
    ): array {
        $mode =
            (string) $validated['mode'];

        $selectedTlpg = trim(
            (string) (
                $validated['tlpg']
                ?? ''
            )
        );

        $selectedTlpgNormalized =
            $selectedTlpg !== ''
                ? $this->normalizeTlpg(
                    $selectedTlpg
                )
                : '';

        $masterCategories =
            $categoryService->master();

        /*
        |--------------------------------------------------------------------------
        | Periode harian
        |--------------------------------------------------------------------------
        */
        if ($mode === 'daily') {
            $date = Carbon::parse(
                $validated['date']
            );

            $startDate =
                $date->copy()
                    ->startOfDay();

            $endDate =
                $date->copy()
                    ->endOfDay();

            $periodLabel =
                $date->translatedFormat(
                    'd F Y'
                );

            $periodFileName =
                $date->format(
                    'Y-m-d'
                );

            $reportSubtitle =
                'SUMMARY OF NOTIFICATION';

            $headerDateText =
                $date
                    ->copy()
                    ->locale('en')
                    ->isoFormat(
                        'dddd, DD MMMM YYYY'
                    );

            $headerHours =
                '00.00 S/D 24.00 WIB';

            $reportNumber =
                $date->format('d') .
                ' /K3-02.2/JATIMBALINUS/' .
                $date->format('Y');
        }

        /*
        |--------------------------------------------------------------------------
        | Periode bulanan
        |--------------------------------------------------------------------------
        */
        else {
            $month =
                Carbon::createFromFormat(
                    '!Y-m',
                    $validated['month']
                );

            $startDate =
                $month->copy()
                    ->startOfMonth();

            $endDate =
                $month->copy()
                    ->endOfMonth();

            $periodLabel =
                $month->translatedFormat(
                    'F Y'
                );

            $periodFileName =
                $month->format('Y-m');

            $reportSubtitle =
                'MONTHLY SUMMARY OF NOTIFICATION';

            $headerDateText =
                $month
                    ->copy()
                    ->locale('en')
                    ->isoFormat(
                        'MMMM YYYY'
                    );

            $headerHours = '-';

            $reportNumber =
                $month->format('m') .
                ' /K3-02.2/JATIMBALINUS/' .
                $month->format('Y');
        }

        $reportTitle =
            'SUMMARY NOTIFICATION ALARM OF AMT / MT';

        /*
         * Ambil data langsung dari sheet agar kendaraan
         * bernilai nol tetap tersedia.
         */
        $sourceRows =
            $dailyService->getReportRows(
                $startDate,
                $endDate
            );

        $vehicles = collect(
            $sourceRows['vehicles']
            ?? []
        );

        $violations = collect(
            $sourceRows['records']
            ?? []
        );

        if ($vehicles->isEmpty()) {
            throw ValidationException::withMessages(
                [
                    'data' =>
                        'Tidak ada daftar kendaraan K3.2 pada periode yang dipilih.',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Laporan harian
        |--------------------------------------------------------------------------
        |
        | Satu kendaraan mengikuti TLPG pada tanggal tersebut.
        |
        */
        if ($mode === 'daily') {
            $vehicles = $vehicles
                ->unique(
                    function (array $row) {
                        return
                            ($row['source_date'] ?? '') .
                            '|' .
                            ($row['nopol'] ?? '');
                    }
                )
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Laporan bulanan
        |--------------------------------------------------------------------------
        |
        | Jika NOPOL berpindah TLPG selama bulan berjalan,
        | gunakan TLPG pada tanggal paling akhir.
        |
        */
        else {
            $vehicles = $vehicles
                ->sortByDesc(
                    function (array $row) {
                        return
                            ($row['source_date'] ?? '') .
                            '|' .
                            str_pad(
                                (string) (
                                    $row['source_row']
                                    ?? 0
                                ),
                                8,
                                '0',
                                STR_PAD_LEFT
                            );
                    }
                )
                ->unique('nopol')
                ->values();
        }

        /*
         * Filter TLPG tetap mengikuti pilihan pengguna.
         */
        if (
            $selectedTlpgNormalized
            !== ''
        ) {
            $vehicles = $vehicles
                ->filter(
                    function (
                        array $row
                    ) use (
                        $selectedTlpgNormalized
                    ) {
                        return
                            $this->normalizeTlpg(
                                $row['tlpg']
                                ?? ''
                            )
                            ===
                            $selectedTlpgNormalized;
                    }
                )
                ->values();
        }

        if ($vehicles->isEmpty()) {
            throw ValidationException::withMessages(
                [
                    'data' =>
                        'Tidak ada kendaraan pada TLPG dan periode yang dipilih.',
                ]
            );
        }

        $groups =
            $this->buildGroups(
                $vehicles,
                $violations,
                $masterCategories,
                $categoryService
            );

        if (empty($groups)) {
            throw ValidationException::withMessages(
                [
                    'data' =>
                        'Data kendaraan ditemukan, tetapi laporan tidak dapat dibentuk.',
                ]
            );
        }

        $grandTotal = collect(
            $groups
        )->sum(
            fn (array $group) =>
                (int) $group['grand_total']
        );

        return [
            'mode' =>
                $mode,

            'reportTitle' =>
                $reportTitle,

            'reportSubtitle' =>
                $reportSubtitle,

            'reportNumber' =>
                $reportNumber,

            'headerDateText' =>
                $headerDateText,

            'headerHours' =>
                $headerHours,

            'projectName' =>
                'Command Centre RTS',

            'regionalName' =>
                'JATIMBALINUS',

            'periodLabel' =>
                $periodLabel,

            'periodFileName' =>
                $periodFileName,

            'selectedTlpg' =>
                $selectedTlpg,

            'masterCategories' =>
                $masterCategories,

            'groups' =>
                $groups,

            'grandTotal' =>
                (int) $grandTotal,

            'generatedAt' =>
                now(),

            'sourceLabel' =>
                'K3-2.2 DAILY',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Membentuk grup TLPG dan seluruh NOPOL
    |--------------------------------------------------------------------------
    */
    private function buildGroups(
        Collection $vehicles,
        Collection $violations,
        array $masterCategories,
        PelanggaranCategoryService $categoryService
    ): array {
        $groups = [];

        /*
         * Menyimpan hubungan NOPOL dengan TLPG.
         */
        $vehicleTlpgMap = [];

        /*
        |--------------------------------------------------------------------------
        | Masukkan seluruh NOPOL lebih dahulu
        |--------------------------------------------------------------------------
        */
        foreach ($vehicles as $vehicle) {
            $nopol =
                $this->normalizeNopol(
                    $vehicle['nopol']
                    ?? ''
                );

            $tlpg =
                $this->normalizeTlpg(
                    $vehicle['tlpg']
                    ?? ''
                );

            if (
                $nopol === '' ||
                $tlpg === ''
            ) {
                continue;
            }

            if (!isset($groups[$tlpg])) {
                $groups[$tlpg] = [
                    'tlpg' =>
                        $tlpg,

                    'rows' =>
                        [],

                    'category_totals' =>
                        array_fill_keys(
                            $masterCategories,
                            0
                        ),

                    'grand_total' =>
                        0,
                ];
            }

            if (
                !isset(
                    $groups[$tlpg]
                    ['rows']
                    [$nopol]
                )
            ) {
                $groups[$tlpg]
                    ['rows']
                    [$nopol] = [
                        'nopol' =>
                            $nopol,

                        'tlpg' =>
                            $tlpg,

                        'driver' =>
                            '-',

                        'counts' =>
                            array_fill_keys(
                                $masterCategories,
                                0
                            ),

                        'total' =>
                            0,
                    ];
            }

            $vehicleTlpgMap[$nopol] =
                $tlpg;
        }

        /*
        |--------------------------------------------------------------------------
        | Tempelkan nilai pelanggaran
        |--------------------------------------------------------------------------
        */
        foreach ($violations as $record) {
            $nopol =
                $this->normalizeNopol(
                    $record['nopol']
                    ?? ''
                );

            if (
                $nopol === '' ||
                !isset(
                    $vehicleTlpgMap[$nopol]
                )
            ) {
                continue;
            }

            $tlpg =
                $vehicleTlpgMap[$nopol];

            $eventName =
                $categoryService->canonicalize(
                    $record['event_name']
                    ?? null
                );

            if (
                !$eventName ||
                !in_array(
                    $eventName,
                    $masterCategories,
                    true
                )
            ) {
                continue;
            }

            $count = max(
                0,
                (int) (
                    $record[
                        'spreadsheet_count'
                    ]
                    ?? 0
                )
            );

            if ($count <= 0) {
                continue;
            }

            $groups[$tlpg]
                ['rows']
                [$nopol]
                ['counts']
                [$eventName] +=
                    $count;

            $groups[$tlpg]
                ['rows']
                [$nopol]
                ['total'] +=
                    $count;

            $groups[$tlpg]
                ['category_totals']
                [$eventName] +=
                    $count;

            $groups[$tlpg]
                ['grand_total'] +=
                    $count;
        }

        /*
         * Urutkan TLPG.
         */
        ksort(
            $groups,
            SORT_NATURAL |
            SORT_FLAG_CASE
        );

        /*
         * Urutkan NOPOL dalam tiap TLPG.
         */
        foreach ($groups as &$group) {
            ksort(
                $group['rows'],
                SORT_NATURAL |
                SORT_FLAG_CASE
            );

            $group['rows'] =
                array_values(
                    $group['rows']
                );
        }

        unset($group);

        return array_values(
            $groups
        );
    }

    private function normalizeTlpg(
        mixed $value
    ): string {
        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }

    private function normalizeNopol(
        mixed $value
    ): string {
        $value = preg_replace(
            '/[^A-Z0-9]+/i',
            ' ',
            (string) $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }

    private function buildFileName(
        string $mode,
        string $period,
        string $tlpg
    ): string {
        $modeLabel =
            $mode === 'daily'
                ? 'harian'
                : 'bulanan';

        $tlpgLabel =
            $tlpg !== ''
                ? preg_replace(
                    '/[^A-Za-z0-9]+/',
                    '-',
                    strtolower($tlpg)
                )
                : 'semua-tlpg';

        $tlpgLabel = trim(
            (string) $tlpgLabel,
            '-'
        );

        return sprintf(
            'laporan-k32-%s-%s-%s.pdf',
            $modeLabel,
            $period,
            $tlpgLabel !== ''
                ? $tlpgLabel
                : 'semua-tlpg'
        );
    }

    private function imageToDataUri(?string $path): ?string
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path);
        $data = base64_encode(file_get_contents($path));

        return 'data:' . $mime . ';base64,' . $data;
    }
}