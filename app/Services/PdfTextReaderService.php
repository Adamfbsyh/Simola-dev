<?php

namespace App\Services;

use App\Models\MonitoringEvent;
use App\Models\ReportUpload;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PdfTextReaderService
{
    /**
     * Mengambil seluruh teks yang tersedia untuk satu event.
     *
     * Sumber:
     * 1. raw_data event;
     * 2. informasi event;
     * 3. file PDF ReportUpload.
     */
    public function readForEvent(
    MonitoringEvent $event
): string {
    $event->loadMissing('reportUpload');

    /*
    |--------------------------------------------------------------------------
    | Prioritas utama: isi PDF asli
    |--------------------------------------------------------------------------
    |
    | Jika PDF berhasil dibaca, jangan gabungkan nama file,
    | description, atau raw_data karena dapat mengganggu extractor.
    |
    */

    $pdfPath = $this->resolvePdfPath(
        $event->reportUpload
    );

    if (
        $pdfPath !== null
        &&
        is_file($pdfPath)
    ) {
        $pdfText = $this->readPdf(
            $pdfPath
        );

        if (trim($pdfText) !== '') {
            return $this->normalizeText(
                $pdfText
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback jika PDF tidak dapat dibaca
    |--------------------------------------------------------------------------
    */

    $parts = [];

    $rawDataText = $this->rawDataToText(
        $event->raw_data
    );

    if ($rawDataText !== '') {
        $parts[] = $rawDataText;
    }

    foreach ([
        $event->event_name ?? null,
        $event->category ?? null,
        $event->description ?? null,
    ] as $value) {
        $value = trim(
            (string) $value
        );

        if ($value !== '') {
            $parts[] = $value;
        }
    }

    return $this->normalizeText(
        implode("\n\n", $parts)
    );
}

    /**
     * Membaca teks PDF.
     *
     * Prioritas:
     * 1. smalot/pdfparser jika sudah terpasang;
     * 2. pdftotext/Poppler.
     */
        public function readPdf(
        string $pdfPath
    ): string {
        if (
            !is_file($pdfPath)
            ||
            strtolower(pathinfo($pdfPath, PATHINFO_EXTENSION)) !== 'pdf'
        ) {
            return '';
        }

        /*
         * Jangan menjalankan smalot/pdfparser di request HTTP utama.
         * PDF malformed/kompleks dapat menghabiskan seluruh memory PHP.
         * pdftotext berjalan sebagai proses eksternal sehingga memory-nya
         * terisolasi dari proses Laravel.
         */
        return $this->readUsingPdftotext($pdfPath);
    }

    /**
     * Menentukan lokasi PDF ReportUpload.
     */
    private function resolvePdfPath(
        ?ReportUpload $upload
    ): ?string {
        if (
            !$upload
            ||
            !$upload->path_file
        ) {
            return null;
        }

        $path = trim(
            (string) $upload->path_file
        );

        if ($path === '') {
            return null;
        }

        /*
         * Path absolut.
         */
        if (is_file($path)) {
            return realpath($path)
                ?: $path;
        }

        /*
         * Default Laravel storage disk.
         */
        try {
            if (Storage::exists($path)) {
                $storagePath = Storage::path(
                    $path
                );

                if (is_file($storagePath)) {
                    return realpath(
                        $storagePath
                    ) ?: $storagePath;
                }
            }
        } catch (Throwable) {
            //
        }

        $cleanPath = ltrim(
            str_replace(
                [
                    'storage/app/',
                    'storage\\app\\',
                    'storage/app/public/',
                    'storage\\app\\public\\',
                    'public/storage/',
                    'public\\storage\\',
                ],
                '',
                $path
            ),
            '/\\'
        );

        $candidates = [
            storage_path(
                'app/' . $cleanPath
            ),

            storage_path(
                'app/public/' . $cleanPath
            ),

            public_path(
                'storage/' . $cleanPath
            ),

            base_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath(
                    $candidate
                ) ?: $candidate;
            }
        }

        return null;
    }

    


    /**
     * Membaca PDF menggunakan pdftotext.
     */
        private function readUsingPdftotext(
        string $pdfPath
    ): string {
        $binary = $this->resolvePdftotextBinary();

        if (!$binary) {
            report(new RuntimeException(
                'pdftotext tidak ditemukan. PDF dilewati agar request Laravel tetap aman.'
            ));
            return '';
        }

        $maxFileBytes = max(1048576, (int) env('PDFTOTEXT_MAX_FILE_BYTES', 67108864));
        $fileSize = @filesize($pdfPath);
        if (is_int($fileSize) && $fileSize > $maxFileBytes) {
            report(new RuntimeException(
                'PDF terlalu besar untuk diproses: '.basename($pdfPath).' ('.$fileSize.' bytes)'
            ));
            return '';
        }

        $timeoutSeconds = max(5, (int) env('PDFTOTEXT_TIMEOUT_SECONDS', 30));
        $maxOutputBytes = max(1048576, (int) env('PDFTOTEXT_MAX_OUTPUT_BYTES', 16777216));
        $maxStderrBytes = 1048576;
        $command = [$binary, '-layout', '-enc', 'UTF-8', $pdfPath, '-'];
        $descriptorSpec = [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
        $process = null; $pipes = [];

        try {
            $process = @proc_open($command, $descriptorSpec, $pipes, dirname($pdfPath), null, ['bypass_shell'=>true]);
            if (!is_resource($process)) return '';
            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
            $stdout=''; $stderr=''; $startedAt=microtime(true); $timedOut=false; $tooLarge=false; $lastStatus=null;

            $drain = static function ($stream, string &$buffer, int $limit, bool &$limitReached): void {
                while (is_resource($stream) && !feof($stream)) {
                    $remaining=$limit-strlen($buffer);
                    if($remaining<=0){$limitReached=true;return;}
                    $chunk=fread($stream,min(8192,$remaining));
                    if($chunk===false||$chunk==='') return;
                    $buffer.=$chunk;
                }
            };

            while (true) {
                $drain($pipes[1],$stdout,$maxOutputBytes,$tooLarge);
                $stderrTooLarge=false; $drain($pipes[2],$stderr,$maxStderrBytes,$stderrTooLarge);
                if($tooLarge){@proc_terminate($process);break;}
                $lastStatus=proc_get_status($process);
                if(!is_array($lastStatus)||!($lastStatus['running']??false)) break;
                if(microtime(true)-$startedAt>$timeoutSeconds){$timedOut=true;@proc_terminate($process);break;}
                usleep(20000);
            }

            $drain($pipes[1],$stdout,$maxOutputBytes,$tooLarge);
            $stderrTooLarge=false; $drain($pipes[2],$stderr,$maxStderrBytes,$stderrTooLarge);
            if(is_resource($pipes[1])) fclose($pipes[1]); if(is_resource($pipes[2])) fclose($pipes[2]);
            $closeCode=proc_close($process); $process=null;

            if($timedOut){ report(new RuntimeException('pdftotext timeout setelah '.$timeoutSeconds.' detik: '.basename($pdfPath))); return ''; }
            if($tooLarge){ report(new RuntimeException('Output pdftotext melewati batas '.$maxOutputBytes.' bytes: '.basename($pdfPath))); return ''; }

            $text=$this->normalizeText($stdout);
            if($text!=='') return $text;

            $exitCode=is_array($lastStatus)&&isset($lastStatus['exitcode'])&&(int)$lastStatus['exitcode']>=0 ? (int)$lastStatus['exitcode'] : $closeCode;
            if($exitCode!==0 && trim($stderr)!==''){
                report(new RuntimeException('pdftotext gagal membaca '.basename($pdfPath).': '.mb_substr(trim($stderr),0,2000)));
            }
            return '';
        } catch (Throwable $e) {
            report($e);
            if(is_resource($process)) @proc_terminate($process);
            foreach($pipes as $pipe) if(is_resource($pipe)) @fclose($pipe);
            if(is_resource($process)) @proc_close($process);
            return '';
        }
    }

    /**
     * Menemukan executable pdftotext.
     */
    private function resolvePdftotextBinary(): ?string
    {
        $envBinary = trim(
            (string) env(
                'PDFTOTEXT_BINARY',
                ''
            ),
            " \t\n\r\0\x0B\"'"
        );

        $candidates = array_filter([
            $envBinary,

            base_path(
                'bin/pdftotext.exe'
            ),

            base_path(
                'tools/poppler/Library/bin/pdftotext.exe'
            ),

            base_path(
                'tools/poppler/bin/pdftotext.exe'
            ),

            'C:\xampp\poppler\Library\bin\pdftotext.exe',

            'C:\xampp\poppler\bin\pdftotext.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath(
                    $candidate
                ) ?: $candidate;
            }
        }

        /*
         * Cari pdftotext pada PATH Windows.
         */
        if (
            PHP_OS_FAMILY === 'Windows'
            &&
            function_exists('shell_exec')
        ) {
            $result = @shell_exec(
                'where.exe pdftotext 2>NUL'
            );

            $firstPath = trim(
                (string) strtok(
                    (string) $result,
                    "\r\n"
                )
            );

            if (
                $firstPath !== ''
                &&
                is_file($firstPath)
            ) {
                return $firstPath;
            }
        }

        /*
         * Cari pada PATH Linux/macOS.
         */
        if (
            PHP_OS_FAMILY !== 'Windows'
            &&
            function_exists('shell_exec')
        ) {
            $result = trim(
                (string) @shell_exec(
                    'command -v pdftotext 2>/dev/null'
                )
            );

            if (
                $result !== ''
                &&
                is_file($result)
            ) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Mengubah seluruh raw_data menjadi teks yang dapat diperiksa.
     */
    private function rawDataToText(
        mixed $rawData
    ): string {
        if ($rawData === null) {
            return '';
        }

        if (is_string($rawData)) {
            $decoded = json_decode(
                $rawData,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE
                &&
                is_array($decoded)
            ) {
                $rawData = $decoded;
            } else {
                return $this->normalizeText(
                    $rawData
                );
            }
        }

        if (is_object($rawData)) {
            $rawData = (array) $rawData;
        }

        if (!is_array($rawData)) {
            return $this->normalizeText(
                (string) $rawData
            );
        }

        $values = [];

        $this->flattenRawData(
            $rawData,
            $values
        );

        return $this->normalizeText(
            implode(
                "\n",
                $values
            )
        );
    }

    private function flattenRawData(
        array $data,
        array &$values
    ): void {
        foreach ($data as $key => $value) {
            /*
             * Simpan nama key agar label seperti nama_amt
             * tetap dapat dikenali extractor.
             */
            if (is_string($key)) {
                $values[] = str_replace(
                    '_',
                    ' ',
                    $key
                );
            }

            if (is_array($value)) {
                $this->flattenRawData(
                    $value,
                    $values
                );

                continue;
            }

            if (is_object($value)) {
                $this->flattenRawData(
                    (array) $value,
                    $values
                );

                continue;
            }

            if (
                is_scalar($value)
                &&
                trim((string) $value) !== ''
            ) {
                $values[] = (string) $value;
            }
        }
    }

    private function normalizeText(
        mixed $value
    ): string {
        $value = (string) $value;

        $value = str_replace(
            [
                "\r\n",
                "\r",
                "\u{00A0}",
            ],
            [
                "\n",
                "\n",
                ' ',
            ],
            $value
        );

        $lines = preg_split(
            '/\n/',
            $value
        );

        $lines = array_map(
            function ($line) {
                $line = preg_replace(
                    '/[ \t]+/',
                    ' ',
                    (string) $line
                );

                return trim(
                    (string) $line
                );
            },
            $lines
        );

        return trim(
            implode(
                "\n",
                $lines
            )
        );
    }
}