<?php

use App\Http\Controllers\Developer\UserAccessController;
use App\Http\Controllers\ErrorlogSheetController;
use App\Http\Controllers\K32CrosscheckController;
use App\Http\Controllers\K32ReportController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MasterFleet\FleetCompanyController;
use App\Http\Controllers\MasterFleet\FleetTerminalController;
use App\Http\Controllers\MasterFleet\MasterFleetController;
use App\Http\Controllers\MasterFleet\MasterFleetImportController;
use App\Http\Controllers\MonitoringDashboardController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadTerpaduController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterFleet\MasterFleetPcSetController;
use App\Http\Controllers\MasterFleet\MasterFleetGroupingController;
use App\Http\Controllers\MasterFleet\FleetVehicleController;
use App\Http\Controllers\MasterFleet\MasterFleetGoogleWorkspaceController;

/*
|--------------------------------------------------------------------------
| Halaman Awal
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    function () {
        return view('welcome');
    }
);

/*
|--------------------------------------------------------------------------
| Route Pengguna Terautentikasi
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
])->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [
            MonitoringDashboardController::class,
            'index',
        ]
    )
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Detail Monitoring Dinamis
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/monitoring/{jenis}',
        [
            MonitoringDashboardController::class,
            'detail',
        ]
    )
        ->middleware('monitoring.access:view')
        ->name('monitoring.detail');

    Route::get(
        '/monitoring/{jenis}/cetak-pdf',
        [
            MonitoringDashboardController::class,
            'cetakPdfGabungan',
        ]
    )
        ->middleware('monitoring.access:export')
        ->name('monitoring.cetak-pdf');

    /*
    |--------------------------------------------------------------------------
    | Profil
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profile',
        [
            ProfileController::class,
            'edit',
        ]
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [
            ProfileController::class,
            'update',
        ]
    )->name('profile.update');

    Route::delete(
        '/profile',
        [
            ProfileController::class,
            'destroy',
        ]
    )->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Laporan Lama
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan',
        [
            LaporanController::class,
            'index',
        ]
    )
        ->middleware('can:upload.history')
        ->name('laporan.index');

    Route::get(
        '/laporan/upload',
        [
            LaporanController::class,
            'upload',
        ]
    )
        ->middleware('can:upload.create')
        ->name('laporan.upload');

    Route::post(
        '/laporan/import',
        [
            LaporanController::class,
            'import',
        ]
    )
        ->middleware('can:upload.create')
        ->name('laporan.import');

    /*
    |--------------------------------------------------------------------------
    | Pelanggaran Lama
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pelanggaran',
        [
            PelanggaranController::class,
            'index',
        ]
    )
        ->middleware('can:pelanggaran.view')
        ->name('pelanggaran.index');

    Route::get(
        '/pelanggaran/upload',
        [
            PelanggaranController::class,
            'upload',
        ]
    )
        ->middleware('can:pelanggaran.create')
        ->name('pelanggaran.upload');

    Route::post(
        '/pelanggaran/import',
        [
            PelanggaranController::class,
            'import',
        ]
    )
        ->middleware('can:pelanggaran.create')
        ->name('pelanggaran.import');

    Route::get(
        '/file-laporan',
        [
            PelanggaranController::class,
            'files',
        ]
    )
        ->middleware('can:pelanggaran.view')
        ->name('pelanggaran.files');

    Route::delete(
        '/file-laporan/{laporanFile}',
        [
            PelanggaranController::class,
            'destroyFile',
        ]
    )
        ->middleware('can:pelanggaran.delete')
        ->name('pelanggaran.files.destroy');

    Route::get(
        '/pelanggaran/export/excel',
        [
            PelanggaranController::class,
            'export',
        ]
    )
        ->middleware('can:pelanggaran.export')
        ->name('pelanggaran.export');

    /*
    |--------------------------------------------------------------------------
    | Upload Terpadu
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/upload-terpadu',
        [
            UploadTerpaduController::class,
            'index',
        ]
    )
        ->middleware('can:upload.view')
        ->name('upload-terpadu.index');

    Route::post(
        '/upload-terpadu',
        [
            UploadTerpaduController::class,
            'store',
        ]
    )
        ->middleware('can:upload.create')
        ->name('upload-terpadu.store');

    /*
    |--------------------------------------------------------------------------
    | Riwayat Upload
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/riwayat-upload',
        [
            UploadTerpaduController::class,
            'files',
        ]
    )
        ->middleware('can:upload.history')
        ->name('upload-terpadu.files');

    Route::delete(
        '/riwayat-upload/{reportUpload}',
        [
            UploadTerpaduController::class,
            'destroy',
        ]
    )
        ->middleware('can:upload.delete')
        ->name('upload-terpadu.destroy');

    Route::get(
        '/riwayat-upload/{reportUpload}/viewer',
        [
            UploadTerpaduController::class,
            'viewerFile',
        ]
    )
        ->middleware('can:upload.history')
        ->name('upload-terpadu.viewer');

    Route::get(
        '/riwayat-upload/{reportUpload}/preview',
        [
            UploadTerpaduController::class,
            'previewFile',
        ]
    )
        ->middleware('can:upload.history')
        ->name('upload-terpadu.preview');

    Route::get(
        '/riwayat-upload/{reportUpload}/download',
        [
            UploadTerpaduController::class,
            'downloadFile',
        ]
    )
        ->middleware('can:upload.history')
        ->name('upload-terpadu.download');

    /*
    |--------------------------------------------------------------------------
    | Crosscheck K3.2
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/crosscheck-k32',
        [
            K32CrosscheckController::class,
            'index',
        ]
    )
        ->middleware('can:crosscheck.view')
        ->name('k32.index');

    Route::post(
        '/crosscheck-k32/sync',
        [
            K32CrosscheckController::class,
            'sync',
        ]
    )
        ->middleware('can:crosscheck.run')
        ->name('k32.sync');

    /*
    |--------------------------------------------------------------------------
    | Errorlog Spreadsheet
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/upload-terpadu/errorlog-spreadsheet',
        [
            ErrorlogSheetController::class,
            'index',
        ]
    )
        ->middleware('can:errorlog.view')
        ->name('errorlog-sheet.index');

    Route::post(
        '/upload-terpadu/errorlog-spreadsheet',
        [
            ErrorlogSheetController::class,
            'store',
        ]
    )
        ->middleware('can:errorlog.update')
        ->name('errorlog-sheet.store');

    Route::post(
        '/upload-terpadu/errorlog-spreadsheet/{source}/sync',
        [
            ErrorlogSheetController::class,
            'sync',
        ]
    )
        ->middleware('can:errorlog.update')
        ->name('errorlog-sheet.sync');

    Route::delete(
        '/upload-terpadu/errorlog-spreadsheet/{source}',
        [
            ErrorlogSheetController::class,
            'destroy',
        ]
    )
        ->middleware('can:errorlog.delete')
        ->name('errorlog-sheet.destroy');

    /*
    |--------------------------------------------------------------------------
    | Laporan K3.2
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan-k32',
        [
            K32ReportController::class,
            'index',
        ]
    )
        ->middleware('can:laporan-k32.view')
        ->name('k32-report.index');

    Route::get(
        '/laporan-k32/preview',
        [
            K32ReportController::class,
            'preview',
        ]
    )
        ->middleware('can:laporan-k32.view')
        ->name('k32-report.preview');

    Route::get(
        '/laporan-k32/pdf',
        [
            K32ReportController::class,
            'pdf',
        ]
    )
        ->middleware('can:laporan-k32.export')
        ->name('k32-report.pdf');
});

/*
|--------------------------------------------------------------------------
| Manajemen Pengguna
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'can:users.access',
])
    ->prefix('developer')
    ->name('developer.')
    ->group(function (): void {
        Route::get(
            '/users',
            [
                UserAccessController::class,
                'index',
            ]
        )->name('users.index');

        Route::get(
            '/users/create',
            [
                UserAccessController::class,
                'create',
            ]
        )->name('users.create');

        Route::post(
            '/users',
            [
                UserAccessController::class,
                'store',
            ]
        )->name('users.store');

        Route::get(
            '/users/{user}/edit',
            [
                UserAccessController::class,
                'edit',
            ]
        )->name('users.edit');

        Route::put(
            '/users/{user}',
            [
                UserAccessController::class,
                'update',
            ]
        )->name('users.update');

        Route::patch(
            '/users/{user}/toggle-active',
            [
                UserAccessController::class,
                'toggleActive',
            ]
        )->name('users.toggle-active');
    });

/*
|--------------------------------------------------------------------------
| Master Fleet Development
|--------------------------------------------------------------------------
*/

if (config('master-fleet.enabled')) {
    Route::prefix('master-fleet')
        ->name('master-fleet.')
        ->middleware([
            'auth',
            'verified',
            'can:master-fleet.view',
        ])
        ->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Halaman Utama Master Fleet
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                [
                    MasterFleetController::class,
                    'index',
                ]
            )->name('index');

            /*
            |--------------------------------------------------------------------------
            | Master TLPG / Terminal
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/terminals',
                [
                    FleetTerminalController::class,
                    'index',
                ]
            )->name('terminals.index');

            Route::get(
                '/terminals/create',
                [
                    FleetTerminalController::class,
                    'create',
                ]
            )
                ->middleware('can:fleet-terminal.create')
                ->name('terminals.create');

            Route::post(
                '/terminals',
                [
                    FleetTerminalController::class,
                    'store',
                ]
            )
                ->middleware('can:fleet-terminal.create')
                ->name('terminals.store');

            Route::get(
                '/terminals/{terminal}/edit',
                [
                    FleetTerminalController::class,
                    'edit',
                ]
            )
                ->middleware('can:fleet-terminal.update')
                ->name('terminals.edit');

            Route::put(
                '/terminals/{terminal}',
                [
                    FleetTerminalController::class,
                    'update',
                ]
            )
                ->middleware('can:fleet-terminal.update')
                ->name('terminals.update');

            Route::patch(
                '/terminals/{terminal}/toggle-active',
                [
                    FleetTerminalController::class,
                    'toggleActive',
                ]
            )
                ->middleware('can:fleet-terminal.disable')
                ->name('terminals.toggle-active');

            /*
            |--------------------------------------------------------------------------
            | Master SPBE / Perusahaan
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/companies',
                [
                    FleetCompanyController::class,
                    'index',
                ]
            )->name('companies.index');

            Route::get(
                '/companies/create',
                [
                    FleetCompanyController::class,
                    'create',
                ]
            )
                ->middleware('can:fleet-company.create')
                ->name('companies.create');

            Route::post(
                '/companies',
                [
                    FleetCompanyController::class,
                    'store',
                ]
            )
                ->middleware('can:fleet-company.create')
                ->name('companies.store');

            Route::get(
                '/companies/{company}/edit',
                [
                    FleetCompanyController::class,
                    'edit',
                ]
            )
                ->middleware('can:fleet-company.update')
                ->name('companies.edit');

            Route::put(
                '/companies/{company}',
                [
                    FleetCompanyController::class,
                    'update',
                ]
            )
                ->middleware('can:fleet-company.update')
                ->name('companies.update');

            Route::patch(
                '/companies/{company}/toggle-active',
                [
                    FleetCompanyController::class,
                    'toggleActive',
                ]
            )
                ->middleware('can:fleet-company.disable')
                ->name('companies.toggle-active');


            /*
            |--------------------------------------------------------------------------
            | Master Kendaraan
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/vehicles',
                [
                    FleetVehicleController::class,
                    'index',
                ]
            )->name('vehicles.index');

            Route::get(
                '/vehicles/create',
                [
                    FleetVehicleController::class,
                    'create',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('vehicles.create');

            Route::post(
                '/vehicles',
                [
                    FleetVehicleController::class,
                    'store',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('vehicles.store');

            Route::get(
                '/vehicles/{vehicle}/edit',
                [
                    FleetVehicleController::class,
                    'edit',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('vehicles.edit');

            Route::put(
                '/vehicles/{vehicle}',
                [
                    FleetVehicleController::class,
                    'update',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('vehicles.update');

            Route::get(
                '/vehicles/{vehicle}/history',
                [
                    FleetVehicleController::class,
                    'history',
                ]
            )->name('vehicles.history');

            Route::patch(
                '/vehicles/{vehicle}/toggle-active',
                [
                    FleetVehicleController::class,
                    'toggleActive',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('vehicles.toggle-active');

            /*
            |--------------------------------------------------------------------------
            | Import Spreadsheet Master Fleet
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/import',
                [
                    MasterFleetImportController::class,
                    'index',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('import.index');

            Route::post(
                '/import/preview',
                [
                    MasterFleetImportController::class,
                    'preview',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('import.preview');

            Route::post(
                '/import/{batch:uuid}/confirm',
                [
                    MasterFleetImportController::class,
                    'confirm',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('import.confirm');


            /*
            |--------------------------------------------------------------------------
            | PC Set Utama
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/pc-set',
                [
                    MasterFleetPcSetController::class,
                    'index',
                ]
            )->name('pc-set.index');


            /*
            |--------------------------------------------------------------------------
            | Google Workspace Master Fleet
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/google-workspace',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'index',
                ]
            )->name('google-workspace.index');

            Route::get(
                '/google-workspace/connect/{purpose}',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'connect',
                ]
            )
                ->where(
                    'purpose',
                    'k302|evidence'
                )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.connect');

            Route::get(
                '/google-workspace/callback',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'callback',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.callback');

            Route::delete(
                '/google-workspace/disconnect/{purpose}',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'disconnect',
                ]
            )
                ->where(
                    'purpose',
                    'k302|evidence'
                )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.disconnect');

            Route::post(
                '/google-workspace/sync-spreadsheet',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'syncSpreadsheet',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.sync-spreadsheet');

            Route::post(
                '/google-workspace/generate-k302-daily',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'generateK302Daily',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.generate-k302-daily');

            Route::post(
                '/google-workspace/generate-evidence',
                [
                    MasterFleetGoogleWorkspaceController::class,
                    'generateEvidence',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('google-workspace.generate-evidence');

            /*
            |--------------------------------------------------------------------------
            | Draft Grouping
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/grouping',
                [
                    MasterFleetGroupingController::class,
                    'index',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.index');

            Route::post(
                '/grouping/draft',
                [
                    MasterFleetGroupingController::class,
                    'createDraft',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.create-draft');

            Route::post(
                '/grouping/{period}/calculate-distances',
                [
                    MasterFleetGroupingController::class,
                    'calculateDistances',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.calculate-distances');

            Route::post(
                '/grouping/{period}/generate',
                [
                    MasterFleetGroupingController::class,
                    'generate',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.generate');

            Route::patch(
                '/grouping/{period}/operator-count',
                [
                    MasterFleetGroupingController::class,
                    'updateOperatorCount',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.operator-count.update');

            Route::post(
                '/grouping/{period}/generate',
                [
                    MasterFleetGroupingController::class,
                    'generate',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.generate');

            Route::patch(
                '/grouping/{period}/assignments/{assignment}',
                [
                    MasterFleetGroupingController::class,
                    'updateAssignment',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.assignments.update');

            Route::post(
                '/grouping/{period}/vehicles',
                [
                    MasterFleetGroupingController::class,
                    'addVehicle',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.vehicles.store');

            Route::post(
                '/grouping/{period}/publish',
                [
                    MasterFleetGroupingController::class,
                    'publish',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.publish');

            Route::post(
                '/grouping/{period}/reset',
                [
                    MasterFleetGroupingController::class,
                    'reset',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.reset');

            Route::patch(
                '/grouping/{period}/operator-count',
                [
                    MasterFleetGroupingController::class,
                    'updateOperatorCount',
                ]
            )
                ->middleware('can:master-fleet.import')
                ->name('grouping.operator-count.update');
        });
    }
require __DIR__ . '/auth.php';