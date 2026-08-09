<?php

use App\Http\Controllers\MasterFleet\MasterFleetAuditController;
use Illuminate\Support\Facades\Route;

if (config('master-fleet.enabled')) {
    Route::prefix(
        'master-fleet'
    )
        ->name(
            'master-fleet.'
        )
        ->middleware([
            'auth',
            'verified',
            'can:master-fleet.view',
        ])
        ->group(
            function (): void {
                Route::get(
                    '/riwayat-perubahan',
                    [
                        MasterFleetAuditController::class,
                        'index',
                    ]
                )
                    ->name(
                        'audit.index'
                    );

                Route::get(
                    '/riwayat-perubahan/export',
                    [
                        MasterFleetAuditController::class,
                        'export',
                    ]
                )
                    ->name(
                        'audit.export'
                    );
            }
        );
}
