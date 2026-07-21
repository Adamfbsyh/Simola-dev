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
            strtolower(
                pathinfo(
                    $pdfPath,
                    PATHINFO_EXTENSION
                )
            ) !== 'pdf'
        ) {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Coba Smalot PDF Parser
        |--------------------------------------------------------------------------
        */

        $smalotText = $this->readUsingSmalot(
            $pdfPath
        );

        if ($smalotText !== '') {
            return $smalotText;
        }

        /*
        |--------------------------------------------------------------------------
        | Coba pdftotext / Poppler
        |--------------------------------------------------------------------------
        */

        return $this->readUsingPdftotext(
            $pdfPath
        );
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
     * Membaca PDF menggunakan smalot/pdfparser jika tersedia.
     */
    private function readUsingSmalot(
        string $pdfPath
    ): string {
        if (
            !class_exists(
                \Smalot\PdfParser\Parser::class
            )
        ) {
            return '';
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();

            $pdf = $parser->parseFile(
                $pdfPath
            );

            return $this->normalizeText(
                $pdf->getText()
            );
        } catch (Throwable $e) {
            report($e);

            return '';
        }
    }

    /**
     * Membaca PDF menggunakan pdftotext.
     */
    private function readUsingPdftotext(
        string $pdfPath
    ): string {
        $binary = $this->resolvePdftotextBinary();

        if (!$binary) {
            return '';
        }

        $command = [
            $binary,
            '-layout',
            '-enc',
            'UTF-8',
            $pdfPath,
            '-',
        ];

        $descriptorSpec = [
            0 => [
                'pipe',
                'r',
            ],

            1 => [
                'pipe',
                'w',
            ],

            2 => [
                'pipe',
                'w',
            ],
        ];

        try {
            $process = @proc_open(
                $command,
                $descriptorSpec,
                $pipes,
                dirname($pdfPath)
            );

            if (!is_resource($process)) {
                return '';
            }

            fclose($pipes[0]);

            $stdout = stream_get_contents(
                $pipes[1]
            );

            fclose($pipes[1]);

            $stderr = stream_get_contents(
                $pipes[2]
            );

            fclose($pipes[2]);

            $exitCode = proc_close(
                $process
            );

            $text = $this->normalizeText(
                $stdout
            );

            /*
             * Beberapa versi pdftotext dapat mengembalikan
             * warning tetapi teks tetap berhasil dibuat.
             */
            if ($text !== '') {
                return $text;
            }

            if (
                $exitCode !== 0
                &&
                trim((string) $stderr) !== ''
            ) {
                report(
                    new RuntimeException(
                        'pdftotext gagal membaca ' .
                        basename($pdfPath) .
                        ': ' .
                        trim((string) $stderr)
                    )
                );
            }

            return '';
        } catch (Throwable $e) {
            report($e);

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