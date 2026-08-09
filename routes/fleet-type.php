<?php

use App\Http\Controllers\MasterFleet\FleetTypeController;
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
                    '/jenis-armada',
                    [
                        FleetTypeController::class,
                        'index',
                    ]
                )
                    ->name(
                        'fleet-type.index'
                    );

                Route::patch(
                    '/jenis-armada/{vehicle}',
                    [
                        FleetTypeController::class,
                        'update',
                    ]
                )
                    ->middleware(
                        'can:master-fleet.import'
                    )
                    ->name(
                        'fleet-type.update'
                    );
            }
        );
}
