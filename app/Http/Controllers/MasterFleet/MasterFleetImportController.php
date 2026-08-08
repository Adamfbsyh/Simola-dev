<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetImportBatch;
use App\Services\MasterFleet\MasterFleetImportExecutionService;
use App\Services\MasterFleet\MasterFleetMigrationAnalysisService;
use App\Services\MasterFleet\SpreadsheetPreviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class MasterFleetImportController extends Controller
{
    /**
     * Halaman import.
     */
    public function index(): View
    {
        return view(
            'master-fleet.import.index',
            [
                'preview' => null,
                'analysis' => null,
                'batch' => null,
            ]
        );
    }

    /**
     * Upload, preview, dan analisis.
     *
     * Belum memasukkan data ke database master.
     */
    public function preview(
        Request $request,
        SpreadsheetPreviewService $previewService,
        MasterFleetMigrationAnalysisService $analysisService
    ): View|RedirectResponse {
        $request->validate(
            [
                'spreadsheet' => [
                    'required',
                    'file',
                    'max:20480',
                    'mimes:xlsx,xls',
                ],
            ],
            [
                'spreadsheet.required' =>
                    'Pilih file spreadsheet terlebih dahulu.',

                'spreadsheet.file' =>
                    'File spreadsheet tidak valid.',

                'spreadsheet.max' =>
                    'Ukuran file maksimal 20 MB.',

                'spreadsheet.mimes' =>
                    'Format workbook yang diterima adalah XLSX atau XLS.',
            ]
        );

        $file =
            $request->file(
                'spreadsheet'
            );

        if (
            $file === null
            ||
            !$file->isValid()
        ) {
            return back()->with(
                'error',
                'File spreadsheet gagal diunggah.'
            );
        }

        $storedPath = null;

        try {
            $preview =
                $previewService->preview(
                    $file
                );

            $analysis =
                $analysisService->analyze(
                    $file
                );

            $uuid =
                (string) Str::uuid();

            $extension =
                strtolower(
                    $file
                        ->getClientOriginalExtension()
                );

            $storedPath =
                $file->storeAs(
                    'master-fleet-imports',
                    $uuid
                    .
                    '.'
                    .
                    $extension,
                    'local'
                );

            if (!$storedPath) {
                throw new RuntimeException(
                    'File tidak berhasil disimpan.'
                );
            }

            $temporaryPath =
                $file->getRealPath();

            $fileHash =
                $temporaryPath
                    ? hash_file(
                        'sha256',
                        $temporaryPath
                    )
                    : hash(
                        'sha256',
                        $uuid
                    );

            $batch =
                FleetImportBatch::query()
                    ->create([
                        'uuid' =>
                            $uuid,

                        'original_name' =>
                            $file
                                ->getClientOriginalName(),

                        'stored_path' =>
                            $storedPath,

                        'file_hash' =>
                            $fileHash,

                        'status' =>
                            'analyzed',

                        'analysis_json' =>
                            $analysis,

                        'uploaded_by' =>
                            $request
                                ->user()
                                ?->id,
                    ]);

            return view(
                'master-fleet.import.index',
                compact(
                    'preview',
                    'analysis',
                    'batch'
                )
            );
        } catch (Throwable $e) {
            report($e);

            if ($storedPath) {
                Storage::disk('local')
                    ->delete(
                        $storedPath
                    );
            }

            $message =
                'Spreadsheet gagal dianalisis.';

            if (app()->isLocal()) {
                $message .=
                    ' '
                    .
                    $e->getMessage();
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    $message
                );
        }
    }

    /**
     * Konfirmasi dan jalankan import resmi.
     */
    public function confirm(
        Request $request,
        FleetImportBatch $batch,
        MasterFleetImportExecutionService $executionService
    ): RedirectResponse {
        $analysis =
            $batch->analysis_json;

        $officialVehicleCount =
            count(
                data_get(
                    $analysis,
                    'final.by_plate',
                    []
                )
            );

        $p1VehicleCount =
            (int) data_get(
                $analysis,
                'summary.p1_vehicle_count',
                0
            );

        $p2VehicleCount =
            (int) data_get(
                $analysis,
                'summary.p2_vehicle_count',
                max(
                    0,
                    $officialVehicleCount
                    -
                    $p1VehicleCount
                )
            );

        $readyForImport =
            (bool) data_get(
                $analysis,
                'summary.ready_for_import',
                false
            );

        $validated =
            $request->validate(
                [
                    'grouping_name' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'effective_date' => [
                        'required',
                        'date',
                    ],

                    'confirmation_count' => [
                        'required',
                        'integer',
                    ],

                    'sync_snapshot' => [
                        'nullable',
                        'boolean',
                    ],
                ],
                [
                    'grouping_name.required' =>
                        'Nama periode grouping wajib diisi.',

                    'effective_date.required' =>
                        'Tanggal berlaku wajib diisi.',

                    'effective_date.date' =>
                        'Tanggal berlaku tidak valid.',
                ]
            );

        if (!$readyForImport) {
            return back()->with(
                'error',
                'Hasil analisis belum aman untuk diimpor. Periksa data invalid pada workbook.'
            );
        }

        if (
            (int) $validated[
                'confirmation_count'
            ]
            !==
            $officialVehicleCount
        ) {
            return back()->with(
                'error',
                'Jumlah konfirmasi kendaraan tidak sesuai dengan PC SET UTAMA.'
            );
        }

        if (
            $officialVehicleCount <= 0
        ) {
            return back()->with(
                'error',
                'Tidak terdapat kendaraan resmi untuk diimpor.'
            );
        }

        try {
            $result =
                $executionService->execute(
                    batch: $batch,
                    userId: (int) $request
                        ->user()
                        ->id,

                    groupingName:
                        $validated[
                            'grouping_name'
                        ],

                    effectiveDate:
                        $validated[
                            'effective_date'
                        ],

                    syncSnapshot:
                        $request->boolean(
                            'sync_snapshot'
                        )
                );

            $message =
                'Import berhasil. '
                .
                $result[
                    'official_vehicle_count'
                ]
                .
                ' kendaraan resmi dan '
                .
                $result[
                    'assignments_created'
                ]
                .
                ' pembagian PC telah disimpan. '
                .
                ($result[
                    'p1_vehicle_count'
                ] ?? $p1VehicleCount)
                .
                ' kendaraan P1 dan '
                .
                ($result[
                    'p2_vehicle_count'
                ] ?? $p2VehicleCount)
                .
                ' kendaraan P2 berhasil dipisahkan.';

            if (
                $result[
                    'company_unresolved'
                ] > 0
            ) {
                $message .=
                    ' Terdapat '
                    .
                    $result[
                        'company_unresolved'
                    ]
                    .
                    ' nama perusahaan yang perlu diperiksa.';
            }

            return redirect()
                ->route(
                    'master-fleet.import.index'
                )
                ->with(
                    'success',
                    $message
                )
                ->with(
                    'import_result',
                    $result
                );
        } catch (Throwable $e) {
            report($e);

            $message =
                'Import gagal dan seluruh perubahan dibatalkan.';

            if (app()->isLocal()) {
                $message .=
                    ' '
                    .
                    $e->getMessage();
            }

            return back()->with(
                'error',
                $message
            );
        }
    }
}