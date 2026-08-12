<?php

use App\Http\Controllers\OperatorChatController;
use App\Http\Controllers\OperatorDeviceAdminController;
use App\Http\Controllers\OperatorDevicePortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Operator Device Mode - TANPA LOGIN
|--------------------------------------------------------------------------
|
| Aktivasi dilakukan sekali menggunakan kode 6 digit dari pengawas.
| Setelah aktif, browser menyimpan device token. Satu identitas PC hanya
| dapat aktif pada satu perangkat sampai pengawas menekan "Lepas Akses".
|
*/
Route::prefix('operator')
    ->name('operator-device.')
    ->group(function (): void {
        Route::get(
            '/',
            [
                OperatorDevicePortalController::class,
                'portal',
            ]
        )->name('portal');

        Route::post(
            '/activate',
            [
                OperatorDevicePortalController::class,
                'activate',
            ]
        )
            ->middleware('throttle:20,1')
            ->name('activate');

        Route::get(
            '/messages',
            [
                OperatorDevicePortalController::class,
                'messages',
            ]
        )
            ->middleware('throttle:60,1')
            ->name('messages');

        Route::post(
            '/messages',
            [
                OperatorDevicePortalController::class,
                'send',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('send');

        Route::post(
            '/notes',
            [
                OperatorDevicePortalController::class,
                'noteStore',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('notes.store');

        Route::delete(
            '/notes/{note}',
            [
                OperatorDevicePortalController::class,
                'noteDestroy',
            ]
        )
            ->middleware('throttle:30,1')
            ->name('notes.destroy');

        Route::post(
            '/notes/{note}/split',
            [
                OperatorDevicePortalController::class,
                'noteSplit',
            ]
        )
            ->middleware('throttle:20,1')
            ->name('notes.split');

        Route::post(
            '/transfers',
            [
                OperatorDevicePortalController::class,
                'transferStore',
            ]
        )
            ->middleware('throttle:20,1')
            ->name('transfers.store');
    });

/*
|--------------------------------------------------------------------------
| Legacy authenticated operator portal
|--------------------------------------------------------------------------
|
| Dibiarkan agar data/role lama tidak rusak. Device Mode di atas adalah
| alur utama baru dan tidak memerlukan akun operator.
|
*/
Route::middleware('auth')
    ->prefix('operator-chat')
    ->name('operator-chat.')
    ->group(function (): void {
        Route::get(
            '/portal',
            [
                OperatorChatController::class,
                'portal',
            ]
        )->name('portal');

        Route::get(
            '/portal/messages',
            [
                OperatorChatController::class,
                'portalMessages',
            ]
        )->name('portal.messages');

        Route::post(
            '/portal/messages',
            [
                OperatorChatController::class,
                'portalSend',
            ]
        )->name('portal.send');

        Route::post(
            '/portal/notes',
            [
                OperatorChatController::class,
                'noteStore',
            ]
        )->name('notes.store');

        Route::delete(
            '/portal/notes/{note}',
            [
                OperatorChatController::class,
                'noteDestroy',
            ]
        )->name('notes.destroy');

        Route::middleware(
            'can:operator-chat.manage'
        )->group(function (): void {
            Route::get(
                '/supervisor',
                [
                    OperatorDeviceAdminController::class,
                    'index',
                ]
            )->name('supervisor.index');

            Route::get(
                '/supervisor/unread',
                [
                    OperatorDeviceAdminController::class,
                    'unread',
                ]
            )->name('supervisor.unread');

            Route::get(
                '/supervisor/{thread}',
                [
                    OperatorDeviceAdminController::class,
                    'show',
                ]
            )->name('supervisor.show');

            Route::get(
                '/supervisor/{thread}/messages',
                [
                    OperatorDeviceAdminController::class,
                    'messages',
                ]
            )->name('supervisor.messages');

            Route::post(
                '/supervisor/{thread}/messages',
                [
                    OperatorDeviceAdminController::class,
                    'send',
                ]
            )->name('supervisor.send');

            Route::patch(
                '/supervisor/{thread}/resolved',
                [
                    OperatorDeviceAdminController::class,
                    'resolve',
                ]
            )->name('supervisor.resolve');

            Route::get(
                '/devices',
                [
                    OperatorDeviceAdminController::class,
                    'devices',
                ]
            )->name('devices.index');

            Route::post(
                '/devices',
                [
                    OperatorDeviceAdminController::class,
                    'deviceStore',
                ]
            )->name('devices.store');

            Route::post(
                '/devices/{device}/activation-code',
                [
                    OperatorDeviceAdminController::class,
                    'activationCode',
                ]
            )->name('devices.code');

            Route::patch(
                '/devices/{device}/release',
                [
                    OperatorDeviceAdminController::class,
                    'release',
                ]
            )->name('devices.release');

            Route::get(
                '/transfers',
                [
                    OperatorDeviceAdminController::class,
                    'transfers',
                ]
            )->name('transfers.index');

            Route::patch(
                '/transfers/{transfer}/approve',
                [
                    OperatorDeviceAdminController::class,
                    'approve',
                ]
            )->name('transfers.approve');

            Route::patch(
                '/transfers/{transfer}/reject',
                [
                    OperatorDeviceAdminController::class,
                    'reject',
                ]
            )->name('transfers.reject');

            /*
             * Alias lama agar bookmark/link v1 tidak putus.
             */
            Route::get(
                '/assignments',
                [
                    OperatorDeviceAdminController::class,
                    'devices',
                ]
            )->name('assignments.index');
        });
    });
