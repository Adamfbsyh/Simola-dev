<?php

use App\Http\Controllers\SimolaHelpController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'verified',
])
    ->prefix('simola-help')
    ->name('simola-help.')
    ->group(function (): void {
        Route::post(
            '/ask',
            [
                SimolaHelpController::class,
                'ask',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('ask');

        Route::middleware('can:users.access')
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get(
                    '/',
                    [
                        SimolaHelpController::class,
                        'adminIndex',
                    ]
                )->name('index');

                Route::post(
                    '/articles',
                    [
                        SimolaHelpController::class,
                        'store',
                    ]
                )->name('articles.store');

                Route::put(
                    '/articles/{article}',
                    [
                        SimolaHelpController::class,
                        'update',
                    ]
                )->name('articles.update');

                Route::delete(
                    '/articles/{article}',
                    [
                        SimolaHelpController::class,
                        'destroy',
                    ]
                )->name('articles.destroy');
            });
    });
