<?php

namespace App\Console\Commands;

use App\Services\K3061DailyService;
use Illuminate\Console\Command;
use Throwable;

class SyncK3061Daily extends Command
{
    protected $signature = 'k3061:sync';

    protected $description =
        'Sinkronisasi data pengemudi dari Google Sheet K3-06.1 Daily';

    public function handle(
        K3061DailyService $service
    ): int {
        $this->newLine();

        $this->info(
            'Memulai sinkronisasi K3-06.1 Daily...'
        );

        try {
            $result = $service->sync();

            $this->newLine();

            $this->info(
                'Sinkronisasi berhasil.'
            );

            $this->table(
                [
                    'Informasi',
                    'Hasil',
                ],
                [
                    [
                        'Spreadsheet',
                        $result['spreadsheet_title'],
                    ],
                    [
                        'Sheet',
                        $result['sheet_name'],
                    ],
                    [
                        'Baris dibaca',
                        $result['rows_read'],
                    ],
                    [
                        'Baris berhasil diproses',
                        $result['rows_parsed'],
                    ],
                    [
                        'Baris disimpan',
                        $result['rows_saved'],
                    ],
                    [
                        'History disimpan',
                        $result['history_saved'],
                    ],
                    [
                        'Daily disimpan',
                        $result['daily_saved'],
                    ],
                    [
                        'Baris dilewati',
                        $result['rows_skipped'],
                    ],
                    [
                        'Tanggal terbaru',
                        $result['latest_date'] ?? '-',
                    ],
                    [
                        'Waktu sinkron',
                        $result['synced_at'],
                    ],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();

            $this->error(
                'Sinkronisasi gagal: ' .
                $e->getMessage()
            );

            report($e);

            return self::FAILURE;
        }
    }
}