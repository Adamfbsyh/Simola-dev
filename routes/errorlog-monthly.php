<?php

use App\Http\Controllers\MasterFleet\MonthlyErrorLogGeneratorController;
use Illuminate\Support\Facades\Route;

if (config('master-fleet.enabled')) {
    Route::prefix('master-fleet')
        ->name('master-fleet.')
        ->middleware([
            'auth',
            'verified',
            'can:master-fleet.view',
        ])
        ->group(function (): void {
            Route::get(
                '/errorlog-monthly',
                [
                    MonthlyErrorLogGeneratorController::class,
                    'index',
                ]
            )->name('errorlog-monthly.index');

            Route::get(
                '/errorlog-monthly/status',
                [
                    MonthlyErrorLogGeneratorController::class,
                    'status',
                ]
            )->name('errorlog-monthly.status');

            Route::post(
                '/errorlog-monthly',
                [
                    MonthlyErrorLogGeneratorController::class,
                    'store',
                ]
            )
                ->name('errorlog-monthly.store');
        });
}
