<?php

namespace App\Console\Commands;

use App\Models\MonitoringEvent;
use App\Services\MonitoringAmtSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncMonitoringAmt extends Command
{
    protected $signature = 'monitoring:sync-amt
                            {--write : Simpan hasil ke database}
                            {--type= : pelanggaran, kendala, atau accident}
                            {--id= : Proses hanya satu ID MonitoringEvent}
                            {--limit=0 : Batas jumlah event yang diproses}';

    protected $description =
        'Mengambil nama AMT dari PDF dan memperbaiki driver_name MonitoringEvent';

    public function handle(
        MonitoringAmtSyncService $service
    ): int {
        $write = (bool) $this->option(
            'write'
        );

        $type = mb_strtolower(
            trim(
                (string) $this->option('type')
            ),
            'UTF-8'
        );

        $eventId = (int) (
            $this->option('id')
            ?: 0
        );

        $limit = max(
            0,
            (int) $this->option('limit')
        );

        $allowedTypes = [
            'pelanggaran',
            'kendala',
            'accident',
        ];

        if (
            $type !== ''
            &&
            !in_array(
                $type,
                $allowedTypes,
                true
            )
        ) {
            $this->error(
                'Nilai --type harus pelanggaran, kendala, atau accident.'
            );

            return self::FAILURE;
        }

        $query = MonitoringEvent::query()
            ->with('reportUpload')
            ->whereIn(
                'event_type',
                $allowedTypes
            )
            ->orderBy('id');

        if ($type !== '') {
            $query->where(
                'event_type',
                $type
            );
        }

        if ($eventId > 0) {
            $query->where(
                'id',
                $eventId
            );
        }

        $this->newLine();

        if ($write) {
            $this->warn(
                'MODE WRITE: perubahan akan disimpan ke database.'
            );
        } else {
            $this->info(
                'MODE PREVIEW: database belum diubah.'
            );

            $this->line(
                'Tambahkan --write setelah hasil preview diperiksa.'
            );
        }

        $stats = [
            'processed' => 0,
            'changed' => 0,
            'extracted' => 0,
            'kept' => 0,
            'cleared' => 0,
            'unresolved' => 0,
            'no_text' => 0,
            'errors' => 0,
        ];

        $previewRows = [];

        foreach (
            $query->lazyById(100)
            as $event
        ) {
            if (
                $limit > 0
                &&
                $stats['processed'] >= $limit
            ) {
                break;
            }

            $stats['processed']++;

            try {
                $result = $service->sync(
                    $event,
                    $write
                );

                if ($result['changed']) {
                    $stats['changed']++;
                }

                if (
                    $result['source']
                    === 'pdf_amt_label'
                ) {
                    $stats['extracted']++;
                } elseif (
                    $result['source']
                    === 'existing_driver_name'
                ) {
                    $stats['kept']++;
                } elseif (
                    $result['source']
                    === 'pdf_text_empty'
                ) {
                    $stats['no_text']++;

                    if (
                        $result['before'] !== null
                        &&
                        $result['after'] === null
                    ) {
                        $stats['cleared']++;
                    }
                } elseif (
                    $result['source']
                    === 'amt_not_found'
                ) {
                    $stats['unresolved']++;

                    if (
                        $result['before'] !== null
                        &&
                        $result['after'] === null
                    ) {
                        $stats['cleared']++;
                    }
                }

                /*
                 * Batasi output agar terminal tidak terlalu panjang.
                 */
                if (
                    (
                        $result['changed']
                        ||
                        $result['source']
                            !== 'existing_driver_name'
                    )
                    &&
                    count($previewRows) < 100
                ) {
                    $previewRows[] = [
                        $result['event_id'],
                        $result['event_type'],
                        $result['nopol'] ?: '-',
                        $result['before'] ?: '-',
                        $result['after'] ?: '-',
                        $result['source'],
                        $result['file_name'] ?: '-',
                    ];
                }
            } catch (Throwable $e) {
                $stats['errors']++;

                $previewRows[] = [
                    $event->id,
                    $event->event_type,
                    $event->nopol ?: '-',
                    $event->driver_name ?: '-',
                    '-',
                    'ERROR: ' . $e->getMessage(),
                    $event->reportUpload?->nama_file
                        ?: '-',
                ];

                report($e);
            }
        }

        $this->newLine();

        if (!empty($previewRows)) {
            $this->table(
                [
                    'ID',
                    'Jenis',
                    'NOPOL',
                    'Sebelum',
                    'Sesudah',
                    'Sumber',
                    'File',
                ],
                $previewRows
            );
        }

        $this->newLine();

        $this->table(
            [
                'Ringkasan',
                'Jumlah',
            ],
            [
                [
                    'Diproses',
                    $stats['processed'],
                ],
                [
                    'Berubah',
                    $stats['changed'],
                ],
                [
                    'AMT ditemukan dari PDF',
                    $stats['extracted'],
                ],
                [
                    'Nama lama dipertahankan',
                    $stats['kept'],
                ],
                [
                    'Nama salah dikosongkan',
                    $stats['cleared'],
                ],
                [
                    'AMT belum ditemukan',
                    $stats['unresolved'],
                ],
                [
                    'Teks PDF kosong',
                    $stats['no_text'],
                ],
                [
                    'Error',
                    $stats['errors'],
                ],
            ]
        );

        $this->newLine();

        if (!$write) {
            $this->warn(
                'Ini masih preview. Jalankan ulang dengan --write untuk menyimpan.'
            );
        } else {
            $this->info(
                'Sinkronisasi nama AMT selesai disimpan.'
            );
        }

        return $stats['errors'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}