<?php

namespace App\Console\Commands;

use App\Models\ErrorlogSheetSource;
use App\Services\GoogleErrorlogSyncService;
use App\Services\K32DailyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoSyncMonitoring extends Command
{
    /**
     * Nama command yang dijalankan Laravel.
     */
    protected $signature = 'monitoring:auto-sync';

    /**
     * Penjelasan command.
     */
    protected $description =
        'Sinkronisasi otomatis Errorlog Spreadsheet dan Form K3.2.';

    public function handle(): int
    {
        $startedAt = now();

        $this->info(
            'Memulai sinkronisasi otomatis SIMOLA...'
        );

        $hasFailure = false;

        /*
        |--------------------------------------------------------------------------
        | Sinkronisasi Errorlog Spreadsheet
        |--------------------------------------------------------------------------
        */
        $errorlogSuccess = 0;
        $errorlogFailed = 0;
        $errorlogTotalData = 0;

        $sources = ErrorlogSheetSource::query()
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('id')
            ->get();

        if ($sources->isEmpty()) {
            $this->warn(
                'Belum ada sumber Spreadsheet Errorlog.'
            );
        } else {
            try {
                /*
                 * Service diambil di dalam try.
                 *
                 * Dengan cara ini, apabila credential Google Errorlog
                 * bermasalah, sinkronisasi K3.2 tetap dapat berjalan.
                 */
                $errorlogService = app(
                    GoogleErrorlogSyncService::class
                );

                foreach ($sources as $source) {
                    try {
                        $source->update([
                            'status' => 'proses',
                            'last_error' => null,
                        ]);

                        $stats = $errorlogService->sync(
                            $source
                        );

                        $errorlogSuccess++;

                        $errorlogTotalData +=
                            (int) ($stats['total'] ?? 0);

                        $this->info(
                            sprintf(
                                'Errorlog %02d-%d berhasil: %d data, %d baru, %d diperbarui, %d tetap.',
                                $source->month,
                                $source->year,
                                $stats['total'] ?? 0,
                                $stats['created'] ?? 0,
                                $stats['updated'] ?? 0,
                                $stats['unchanged'] ?? 0
                            )
                        );
                    } catch (\Throwable $e) {
                        $hasFailure = true;
                        $errorlogFailed++;

                        $source->update([
                            'status' => 'gagal',

                            'last_error' => mb_substr(
                                $e->getMessage(),
                                0,
                                2000
                            ),
                        ]);

                        Log::error(
                            'Sinkronisasi otomatis Errorlog gagal.',
                            [
                                'source_id' =>
                                    $source->id,

                                'spreadsheet_id' =>
                                    $source->spreadsheet_id,

                                'sheet_name' =>
                                    $source->sheet_name,

                                'periode' =>
                                    sprintf(
                                        '%02d-%d',
                                        $source->month,
                                        $source->year
                                    ),

                                'message' =>
                                    $e->getMessage(),
                            ]
                        );

                        $this->error(
                            sprintf(
                                'Errorlog %02d-%d gagal: %s',
                                $source->month,
                                $source->year,
                                $e->getMessage()
                            )
                        );
                    }
                }
            } catch (\Throwable $e) {
                $hasFailure = true;
                $errorlogFailed = $sources->count();

                Log::error(
                    'Service sinkronisasi Errorlog tidak dapat dijalankan.',
                    [
                        'message' => $e->getMessage(),
                    ]
                );

                $this->error(
                    'Errorlog tidak dapat disinkronkan: ' .
                    $e->getMessage()
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sinkronisasi Form K3.2
        |--------------------------------------------------------------------------
        |
        | Method ini juga:
        | - Mengambil data K3.2 dari Google Spreadsheet.
        | - Menyimpan data ke k32_daily_records.
        | - Membaca ulang jam kejadian pada PDF.
        |
        */
        $k32Success = false;
        $k32Stats = [];

        try {
            $k32Service = app(
                K32DailyService::class
            );

            $k32Stats =
                $k32Service->syncFromGoogleSheet();

            $k32Success = true;

            $this->info(
                sprintf(
                    'K3.2 berhasil: %d kombinasi, total %d kejadian.',
                    $k32Stats['records_saved'] ?? 0,
                    $k32Stats['total_value'] ?? 0
                )
            );

            $this->info(
                sprintf(
                    'PDF diperiksa: %d file, jam diperbarui: %d data dari %d file.',
                    $k32Stats['pdf_files_checked'] ?? 0,
                    $k32Stats['pdf_events_updated'] ?? 0,
                    $k32Stats['pdf_files_updated'] ?? 0
                )
            );

            if (
                (int) (
                    $k32Stats['pdf_time_not_found']
                    ?? 0
                ) > 0
            ) {
                $this->warn(
                    sprintf(
                        'Jam tidak ditemukan pada %d file PDF.',
                        $k32Stats['pdf_time_not_found']
                            ?? 0
                    )
                );
            }

            if (
                (int) (
                    $k32Stats['pdf_file_missing']
                    ?? 0
                ) > 0
            ) {
                $this->warn(
                    sprintf(
                        'Sebanyak %d file PDF tidak ditemukan.',
                        $k32Stats['pdf_file_missing']
                            ?? 0
                    )
                );
            }

            if (
                (int) (
                    $k32Stats['pdf_read_failed']
                    ?? 0
                ) > 0
            ) {
                $this->warn(
                    sprintf(
                        'Sebanyak %d file PDF gagal dibaca.',
                        $k32Stats['pdf_read_failed']
                            ?? 0
                    )
                );
            }
        } catch (\Throwable $e) {
            $hasFailure = true;

            Log::error(
                'Sinkronisasi otomatis K3.2 gagal.',
                [
                    'message' => $e->getMessage(),
                ]
            );

            $this->error(
                'Sinkronisasi K3.2 gagal: ' .
                $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ringkasan
        |--------------------------------------------------------------------------
        */
        $duration = $startedAt->diffInSeconds(
            now()
        );

        $this->newLine();

        $this->info(
            '===== HASIL SINKRONISASI ====='
        );

        $this->line(
            'Errorlog berhasil : ' .
            $errorlogSuccess .
            ' sumber'
        );

        $this->line(
            'Errorlog gagal    : ' .
            $errorlogFailed .
            ' sumber'
        );

        $this->line(
            'Total Errorlog    : ' .
            number_format(
                $errorlogTotalData
            ) .
            ' data'
        );

        $this->line(
            'K3.2              : ' .
            (
                $k32Success
                    ? 'Berhasil'
                    : 'Gagal'
            )
        );

        $this->line(
            'Durasi            : ' .
            $duration .
            ' detik'
        );

        $this->line(
            'Selesai pada      : ' .
            now()->format('d-m-Y H:i:s')
        );

        if ($hasFailure) {
            $this->warn(
                'Sinkronisasi selesai, tetapi ada proses yang gagal.'
            );

            return self::FAILURE;
        }

        $this->info(
            'Seluruh sinkronisasi berhasil.'
        );

        return self::SUCCESS;
    }
}