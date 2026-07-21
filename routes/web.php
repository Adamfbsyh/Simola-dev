<?php

use App\Http\Controllers\Developer\UserAccessController;
use App\Http\Controllers\ErrorlogSheetController;
use App\Http\Controllers\K32CrosscheckController;
use App\Http\Controllers\K32ReportController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MonitoringDashboardController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadTerpaduController;
use Illuminate\Support\Facades\Route;

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
])->group(function () {
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
    | Profile
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
    ->group(function () {
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

require __DIR__ . '/auth.php';