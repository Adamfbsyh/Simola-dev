<?php

namespace App\Observers;

use App\Models\MonitoringEvent;
use App\Services\MonitoringAmtSyncService;
use Throwable;

class MonitoringEventObserver
{
    /**
     * Event yang membutuhkan nama AMT.
     */
    private const SUPPORTED_TYPES = [
        'pelanggaran',
        'kendala',
        'accident',
    ];

    /**
     * Dijalankan sesudah MonitoringEvent disimpan.
     */
    public function saved(
        MonitoringEvent $monitoringEvent
    ): void {
        $eventType = mb_strtolower(
            trim(
                (string) $monitoringEvent->event_type
            ),
            'UTF-8'
        );

        if (
            !in_array(
                $eventType,
                self::SUPPORTED_TYPES,
                true
            )
        ) {
            return;
        }

        /*
         * Pada create selalu diperiksa.
         *
         * Pada update hanya diperiksa kembali jika kolom yang
         * berhubungan dengan PDF atau AMT berubah.
         */
        if (
            !$monitoringEvent->wasRecentlyCreated
            &&
            !$monitoringEvent->wasChanged([
                'driver_name',
                'raw_data',
                'report_upload_id',
                'event_type',
            ])
        ) {
            return;
        }

        try {
            app(
                MonitoringAmtSyncService::class
            )->sync(
                $monitoringEvent,
                true
            );
        } catch (Throwable $e) {
            /*
             * Upload tidak boleh gagal hanya karena ekstraksi AMT.
             * Error tetap dicatat di laravel.log.
             */
            report($e);
        }
    }
}