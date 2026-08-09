<?php

use App\Http\Controllers\MasterFleet\MasterFleetCompareController;
use Illuminate\Support\Facades\Route;

if (config('master-fleet.enabled')) {
    Route::prefix('master-fleet/import/compare')
        ->name('master-fleet.compare.')
        ->middleware([
            'auth',
            'verified',
            'can:master-fleet.import',
        ])
        ->group(function (): void {
            Route::get('/', [MasterFleetCompareController::class, 'index'])
                ->name('index');

            Route::post('/upload', [MasterFleetCompareController::class, 'upload'])
                ->name('upload');

            Route::get('/{batch}', [MasterFleetCompareController::class, 'show'])
                ->name('show');

            Route::post('/{batch}/apply', [MasterFleetCompareController::class, 'apply'])
                ->name('apply');

            Route::get('/{batch}/download', [MasterFleetCompareController::class, 'download'])
                ->name('download');

            Route::get('/{batch}/export', [MasterFleetCompareController::class, 'export'])
                ->name('export');
        });
}
