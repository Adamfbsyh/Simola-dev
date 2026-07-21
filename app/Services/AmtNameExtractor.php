<?php

namespace App\Services;

use Illuminate\Support\Str;

class AmtNameExtractor
{
    /**
     * Mengambil nama AMT dari teks PDF.
     *
     * @param string|null $pdfText
     * @param string|null $expectedNopol NOPOL event dari database.
     */
    public function extract(
        ?string $pdfText,
        ?string $expectedNopol = null
    ): ?string {
        $text = $this->prepareText($pdfText);

        if ($text === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Prioritas utama: field nama AMT yang jelas
        |--------------------------------------------------------------------------
        */

        $name = $this->extractFromStrictLabels($text);

        if ($name !== null) {
            return $name;
        }

        $lines = preg_split('/\R/u', $text);

        if (!is_array($lines)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cari nama berdasarkan NOPOL event yang sama
        |--------------------------------------------------------------------------
        |
        | Contoh hasil pdftotext:
        |
        | MUDJITO
        | N 9424 UU
        |
        */

        if (
            $expectedNopol !== null
            &&
            trim($expectedNopol) !== ''
        ) {
            $name = $this->extractNearExpectedNopol(
                $lines,
                $expectedNopol
            );

            if ($name !== null) {
                return $name;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Fallback terakhir: nama sebelum baris berbentuk NOPOL
        |--------------------------------------------------------------------------
        */

        return $this->extractBeforeAnyNopol($lines);
    }

    /**
     * Membaca field AMT dengan label yang jelas.
     */
    private function extractFromStrictLabels(
        string $text
    ): ?string {
        /*
         * Pattern wajib berada di awal baris.
         * Ini mencegah judul "LAPORAN PELANGGARAN AMT"
         * dianggap sebagai field nama.
         */
        $patterns = [
            '/^[ \t]*NAMA[ \t]+AMT[ \t]*1[ \t]*\/[ \t]*AMT[ \t]*2[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*AMT[ \t]*1[ \t]*\/[ \t]*AMT[ \t]*2[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+AMT[ \t]*1[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+AMT[ \t]*2[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+AMT[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+PENGEMUDI[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*PENGEMUDI[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+DRIVER[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*DRIVER[ \t]*[:\-][ \t]*(.+)$/imu',

            '/^[ \t]*NAMA[ \t]+AWAK[ \t]+MOBIL[ \t]+TANGKI[ \t]*[:\-][ \t]*(.+)$/imu',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $text, $matches)) {
                continue;
            }

            foreach ($matches[1] as $candidate) {
                $name = $this->cleanCandidate($candidate);

                if ($this->isValidAmtName($name)) {
                    return $name;
                }
            }
        }

        /*
         * Field berada pada baris berikutnya.
         */
        $lines = preg_split('/\R/u', $text);

        if (!is_array($lines)) {
            return null;
        }

        foreach ($lines as $index => $line) {
            if (!$this->isStandaloneAmtLabel($line)) {
                continue;
            }

            for (
                $nextIndex = $index + 1;
                $nextIndex <= $index + 3;
                $nextIndex++
            ) {
                if (!isset($lines[$nextIndex])) {
                    break;
                }

                $candidate = trim((string) $lines[$nextIndex]);

                if ($candidate === '') {
                    continue;
                }

                $name = $this->cleanCandidate($candidate);

                if ($this->isValidAmtName($name)) {
                    return $name;
                }

                if ($this->looksLikeAnotherField($candidate)) {
                    break;
                }
            }
        }

        return null;
    }

    /**
     * Mengambil nama di sekitar NOPOL event yang diketahui.
     */
    private function extractNearExpectedNopol(
        array $lines,
        string $expectedNopol
    ): ?string {
        $lineCount = count($lines);

        for ($index = 0; $index < $lineCount; $index++) {
            $currentLine = trim(
                (string) $lines[$index]
            );

            if (
                $currentLine === ''
                ||
                $this->isMetadataLine($currentLine)
                ||
                !$this->lineContainsNopol(
                    $currentLine,
                    $expectedNopol
                )
            ) {
                continue;
            }

            /*
            * Coba nama dan NOPOL pada satu baris.
            */
            $sameLineCandidate =
                $this->extractBeforeNopolOnSameLine(
                    $currentLine,
                    $expectedNopol
                );

            if (
                $this->isValidAmtName(
                    $sameLineCandidate
                )
            ) {
                return $sameLineCandidate;
            }

            /*
            * Cari hanya satu baris bukan-kosong tepat sebelumnya.
            *
            * Tidak lagi mencari sampai empat baris karena berisiko
            * mengambil Kepada Yth, Site, Lead, SPV, dan label lain.
            */
            for (
                $previousIndex = $index - 1;
                $previousIndex >= 0;
                $previousIndex--
            ) {
                $previousLine = trim(
                    (string) $lines[$previousIndex]
                );

                if ($previousLine === '') {
                    continue;
                }

                /*
                * Baris pertama sebelum NOPOL adalah label formulir.
                * Berarti nama AMT memang tidak ditemukan.
                */
                if (
                    $this->isMetadataLine($previousLine)
                    ||
                    $this->looksLikeFormLabel($previousLine)
                    ||
                    $this->looksLikeAnotherField($previousLine)
                ) {
                    return null;
                }

                $candidate = $this->cleanCandidate(
                    $previousLine
                );

                return $this->isValidAmtName($candidate)
                    ? $candidate
                    : null;
            }
        }

        return null;
    }

    /**
     * Fallback jika NOPOL database tidak tersedia.
     */
    private function extractBeforeAnyNopol(
        array $lines
    ): ?string {
        $lineCount = min(
            count($lines),
            180
        );

        for ($index = 0; $index < $lineCount; $index++) {
            $currentLine = trim(
                (string) $lines[$index]
            );

            if (
                $currentLine === ''
                ||
                !$this->isNopolLine($currentLine)
            ) {
                continue;
            }

            /*
            * Hanya membaca satu baris bukan-kosong sebelumnya.
            */
            for (
                $previousIndex = $index - 1;
                $previousIndex >= 0;
                $previousIndex--
            ) {
                $previousLine = trim(
                    (string) $lines[$previousIndex]
                );

                if ($previousLine === '') {
                    continue;
                }

                if (
                    $this->isMetadataLine($previousLine)
                    ||
                    $this->looksLikeFormLabel($previousLine)
                    ||
                    $this->looksLikeAnotherField($previousLine)
                ) {
                    break;
                }

                $candidate = $this->cleanCandidate(
                    $previousLine
                );

                if ($this->isValidAmtName($candidate)) {
                    return $candidate;
                }

                break;
            }
        }

        return null;
    }

    /**
     * Mengambil teks sebelum NOPOL jika berada satu baris.
     */
    private function extractBeforeNopolOnSameLine(
        string $line,
        string $nopol
    ): ?string {
        $pattern = $this->buildNopolRegex($nopol);

        if ($pattern === null) {
            return null;
        }

        $parts = preg_split(
            $pattern,
            $line,
            2
        );

        if (
            !is_array($parts)
            ||
            count($parts) < 2
        ) {
            return null;
        }

        $candidate = trim(
            (string) $parts[0]
        );

        /*
         * Baris seperti:
         * Nopol / Alat Kerja : N 9424 UU
         *
         * Bagian sebelum NOPOL hanyalah label, bukan nama.
         */
        if (
            preg_match(
                '/\b(?:NOPOL|NO\s*POL|NOMOR\s+POLISI|ALAT\s+KERJA)\b/iu',
                $candidate
            )
        ) {
            return null;
        }

        return $this->cleanCandidate(
            $candidate
        );
    }

    /**
     * Memastikan baris memuat NOPOL event.
     */
    private function lineContainsNopol(
        string $line,
        string $expectedNopol
    ): bool {
        $pattern = $this->buildNopolRegex(
            $expectedNopol
        );

        if ($pattern === null) {
            return false;
        }

        return preg_match(
            $pattern,
            $line
        ) === 1;
    }

    /**
     * Membuat regex NOPOL yang toleran terhadap spasi.
     */
    private function buildNopolRegex(
        string $nopol
    ): ?string {
        $normalized = $this->normalizeKey(
            $nopol
        );

        $parts = preg_split(
            '/\s+/',
            $normalized
        );

        if (
            !is_array($parts)
            ||
            count($parts) < 2
        ) {
            return null;
        }

        $escapedParts = array_map(
            fn ($part) => preg_quote(
                $part,
                '/'
            ),
            $parts
        );

        return '/\b' .
            implode(
                '[\s\-\.]*',
                $escapedParts
            ) .
            '\b/iu';
    }

    /**
     * Mendeteksi baris berbentuk NOPOL.
     */
    private function isNopolLine(
        string $value
    ): bool {
        $key = $this->normalizeKey(
            $value
        );

        $key = preg_replace(
            '/^(?:' .
            'NOPOL|' .
            'NO POL|' .
            'NOMOR POLISI|' .
            'NOPOL ALAT KERJA|' .
            'ALAT KERJA' .
            ')\s*/u',
            '',
            $key
        );

        return preg_match(
            '/^[A-Z]{1,3}\s+\d{1,4}\s+[A-Z]{1,3}$/u',
            trim((string) $key)
        ) === 1;
    }

    /**
     * Membersihkan kandidat nama.
     */
    private function cleanCandidate(
        mixed $value
    ): ?string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        /*
         * Potong ketika terdapat field berikutnya.
         */
        $parts = preg_split(
            '/\b(?:' .
            'NOPOL|' .
            'NO\s*POL|' .
            'NOMOR\s+POLISI|' .
            'ALAT\s+KERJA|' .
            'PERUSAHAAN|' .
            'REGIONAL|' .
            'TERMINAL|' .
            'TLPG|' .
            'STATUS|' .
            'TANGGAL|' .
            'WAKTU|' .
            'SHIFT|' .
            'JABATAN|' .
            'LEAD|' .
            'LEADER|' .
            'TEAM\s+LEADER|' .
            'SPV|' .
            'SUPERVISOR|' .
            'CHECKER|' .
            'PETUGAS|' .
            'DILAPORKAN\s+OLEH|' .
            'DITERIMA\s+OLEH' .
            ')\b/iu',
            $value,
            2
        );

        $value = $parts[0] ?? '';

        /*
         * Hilangkan label AMT apabila masih terbawa.
         */
        $value = preg_replace(
            '/^(?:' .
            'NAMA\s+AMT\s*1\s*\/\s*AMT\s*2|' .
            'AMT\s*1\s*\/\s*AMT\s*2|' .
            'NAMA\s+AMT\s*[12]?|' .
            'AMT\s*[12]?|' .
            'NAMA\s+PENGEMUDI|' .
            'PENGEMUDI|' .
            'NAMA\s+DRIVER|' .
            'DRIVER|' .
            'NAMA\s+AWAK\s+MOBIL\s+TANGKI|' .
            'AWAK\s+MOBIL\s+TANGKI' .
            ')\s*[:\-]?\s*/iu',
            '',
            (string) $value
        );

        $value = str_replace(
            [
                "\t",
                '|',
                "\u{00A0}",
            ],
            ' ',
            (string) $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim((string) $value)
        );

        $value = trim(
            (string) $value,
            " \t\n\r\0\x0B:;-_"
        );

        if ($value === '') {
            return null;
        }

        return mb_strtoupper(
            $value,
            'UTF-8'
        );
    }

    /**
     * Validasi bahwa kandidat benar-benar nama AMT.
     */
    private function isValidAmtName(
        ?string $value
    ): bool {
        if ($value === null) {
            return false;
        }

        if ($this->looksLikeFormLabel($value)) {
            return false;
        }

        /*
        * Kandidat hasil fallback yang masih mengandung titik dua
        * adalah label formulir, bukan nama orang.
        */
        if (
            str_contains(
                (string) $value,
                ':'
            )
        ) {
            return false;
        }

        $key = $this->normalizeKey(
            $value
        );

        if ($key === '') {
            return false;
        }

        $invalidExact = [
            'LAPORAN',
            'LAPORAN AMT',
            'LAPORAN PELANGGARAN',
            'LAPORAN PELANGGARAN AMT',
            'LAPORAN KENDALA',
            'LAPORAN ACCIDENT',
            'LAPORAN KECELAKAAN',

            'NAMA FILE',
            'FILE',
            'FILE PDF',
            'PDF',
            'PDF TEXT',
            'RAW DATA',
            'SUMBER',
            'SOURCE',
            'SOURCE FILE',
            'SOURCE PAGE',
            'DATA PELANGGARAN HARIAN DARI FILE PDF',
            'DATA KENDALA DARI FILE PDF',
            'DATA ACCIDENT DARI FILE PDF',

            'KEPADA',
            'KEPADA YTH',
            'KEPADA YTH SITE',
            'KEPADA YTH SITE SUPERVISOR',

            'SITE',
            'SITE SUPERVISOR',
            'SITE SPV',

            'OPERATIONAL AREA MANAGER',
            'AREA MANAGER',

            'NAMA FILE',

            'OVER SPEED',
            'OVERSPEED',
            'SPEED',
            'LOW',
            'MEDIUM',
            'HIGH',
            'CRITICAL',
            'PASIF',
            'AKTIF',

            'PELANGGARAN',
            'KENDALA',
            'ACCIDENT',
            'KECELAKAAN',
            'INSIDEN',
            'KEJADIAN',

            'HASIL PEMERIKSAAN',
            'JENIS PELANGGARAN',
            'URAIAN KRONOLOGI',
            'LOKASI KEJADIAN',
            'EVIDENCE',

            'MT',
            'MT NOPOL',
            'NOPOL',
            'NO POL',
            'NOMOR POLISI',

            'AMT',
            'AMT 1',
            'AMT 2',
            'NAMA AMT',
            'PENGEMUDI',
            'NAMA PENGEMUDI',
            'DRIVER',
            'NAMA DRIVER',

            'LEAD',
            'LEADER',
            'TEAM LEAD',
            'TEAM LEADER',
            'SPV',
            'SUPERVISOR',
            'CHECKER',
            'PETUGAS',
            'ADMIN',
            'SECURITY',
            'MANAGER',
            'OPERATIONAL AREA MANAGER',

            'DILAPORKAN OLEH',
            'DITERIMA OLEH',

            'FORM',
            'NOMOR',
            'PROYEK',
            'LOKASI',
            'REGIONAL',
            'PERUSAHAAN',
            'TERMINAL',
            'TLPG',
            'SHIFT',
            'TANGGAL',
            'WAKTU',
            'STATUS',
            'JABATAN',

            'NO',
            'TOTAL',
            'JUMLAH',
        ];

        if (
            in_array(
                $key,
                $invalidExact,
                true
            )
        ) {
            return false;
        }

        /*
         * Tolak kalimat laporan, pelanggaran, lokasi,
         * serta nama jabatan non-AMT.
         */
        if (
            preg_match(
                '/\b(?:' .
                'LAPORAN|' .
                'PELANGGARAN|' .
                'KENDALA|' .
                'ACCIDENT|' .
                'KECELAKAAN|' .
                'INSIDEN|' .
                'KRONOLOGI|' .
                'PEMERIKSAAN|' .
                'SEVERITY|' .
                'RESPONSE|' .
                'OVER\s*SPEED|' .
                'KECEPATAN|' .
                'LOKASI|' .
                'EVIDENCE|' .
                'LEAD|' .
                'LEADER|' .
                'SPV|' .
                'SUPERVISOR|' .
                'CHECKER|' .
                'PETUGAS|' .
                'SECURITY|' .
                'ADMIN|' .
                'MANAGER|' .
                'PENGAWAS|' .
                'KEPALA' .
                ')\b/u',
                $key
            )
        ) {
            return false;
        }

        /*
         * Tolak NOPOL.
         */
        if (
            preg_match(
                '/^[A-Z]{1,3}\s*\d{1,4}\s*[A-Z]{0,3}$/u',
                $key
            )
        ) {
            return false;
        }

        /*
         * Nama wajib mengandung huruf.
         */
        if (
            preg_match(
                '/[A-Z]/u',
                $key
            ) !== 1
        ) {
            return false;
        }

        /*
         * Nama manusia tidak boleh memiliki banyak angka.
         */
        if (
            preg_match_all('/\d/u', $key) > 1
        ) {
            return false;
        }

        $words = preg_split(
            '/\s+/u',
            $key
        );

        $wordCount = is_array($words)
            ? count($words)
            : 0;

        return $wordCount >= 1
            && $wordCount <= 7;
    }

    private function isStandaloneAmtLabel(
        mixed $value
    ): bool {
        $key = $this->normalizeKey(
            $value
        );

        return in_array(
            $key,
            [
                'NAMA AMT 1 AMT 2',
                'AMT 1 AMT 2',
                'NAMA AMT 1',
                'NAMA AMT 2',
                'AMT 1',
                'AMT 2',
                'NAMA AMT',
                'NAMA PENGEMUDI',
                'PENGEMUDI',
                'NAMA DRIVER',
                'DRIVER',
                'NAMA AWAK MOBIL TANGKI',
                'AWAK MOBIL TANGKI',
            ],
            true
        );
    }

    private function looksLikeAnotherField(
        mixed $value
    ): bool {
        $key = $this->normalizeKey(
            $value
        );

        return preg_match(
            '/^(?:' .
            'NOPOL|' .
            'NO POL|' .
            'NOMOR POLISI|' .
            'PERUSAHAAN|' .
            'TERMINAL|' .
            'TLPG|' .
            'STATUS|' .
            'TANGGAL|' .
            'WAKTU|' .
            'LEAD|' .
            'LEADER|' .
            'SPV|' .
            'SUPERVISOR|' .
            'DILAPORKAN OLEH|' .
            'DITERIMA OLEH' .
            ')\b/u',
            $key
        ) === 1;
    }

    /**
 * Mendeteksi nama file, URL, dan metadata,
 * agar tidak dianggap sebagai nama AMT.
 */
private function isMetadataLine(
    mixed $value
): bool {
    $rawValue = trim(
        (string) $value
    );

    if ($rawValue === '') {
        return true;
    }

    $key = $this->normalizeKey(
        $rawValue
    );

    if (
        str_contains(
            mb_strtolower($rawValue, 'UTF-8'),
            '.pdf'
        )
    ) {
        return true;
    }

    if (
        str_contains(
            mb_strtolower($rawValue, 'UTF-8'),
            'http://'
        )
        ||
        str_contains(
            mb_strtolower($rawValue, 'UTF-8'),
            'https://'
        )
    ) {
        return true;
    }

    $metadataValues = [
        'NAMA FILE',
        'FILE',
        'FILE PDF',
        'PDF TEXT',
        'RAW DATA',
        'SOURCE',
        'SOURCE FILE',
        'SOURCE PAGE',
        'SUMBER',
        'SP 1',
    ];

    if (
        in_array(
            $key,
            $metadataValues,
            true
        )
    ) {
        return true;
    }

    if (
        str_starts_with(
            $key,
            'DATA PELANGGARAN HARIAN DARI FILE PDF'
        )
        ||
        str_starts_with(
            $key,
            'DATA KENDALA DARI FILE PDF'
        )
        ||
        str_starts_with(
            $key,
            'DATA ACCIDENT DARI FILE PDF'
        )
    ) {
        return true;
    }

    return false;
}

/**
 * Menolak label formulir agar tidak dianggap sebagai nama AMT.
 */
private function looksLikeFormLabel(
    mixed $value
): bool {
    $rawValue = trim(
        (string) $value
    );

    if ($rawValue === '') {
        return true;
    }

    $key = $this->normalizeKey(
        $rawValue
    );

    /*
     * Nama AMT hasil ekstraksi seharusnya tidak memiliki
     * struktur label dengan tanda titik dua.
     */
    if (
        str_contains($rawValue, ':')
        &&
        preg_match(
            '/^(?:' .
            'KEPADA YTH|' .
            'KEPADA|' .
            'SITE|' .
            'SITE SUPERVISOR|' .
            'SITE SPV|' .
            'DILAPORKAN OLEH|' .
            'DITERIMA OLEH|' .
            'TEAM LEADER|' .
            'LEAD|' .
            'SPV|' .
            'SUPERVISOR|' .
            'NAMA FILE|' .
            'NOMOR|' .
            'PROYEK|' .
            'LOKASI|' .
            'REGIONAL|' .
            'PERUSAHAAN|' .
            'TERMINAL|' .
            'TLPG|' .
            'STATUS MUATAN|' .
            'STATUS|' .
            'JENIS PELANGGARAN|' .
            'URAIAN KRONOLOGI|' .
            'LOKASI KEJADIAN|' .
            'HASIL PEMERIKSAAN|' .
            'EVIDENCE' .
            ')\b/u',
            $key
        )
    ) {
        return true;
    }

    return preg_match(
        '/^(?:' .
        'KEPADA YTH|' .
        'KEPADA YTH SITE|' .
        'KEPADA YTH SITE SUPERVISOR|' .
        'SITE|' .
        'SITE SUPERVISOR|' .
        'SITE SPV|' .
        'DILAPORKAN OLEH|' .
        'DITERIMA OLEH|' .
        'TEAM LEADER|' .
        'LEAD|' .
        'LEADER|' .
        'SPV|' .
        'SUPERVISOR|' .
        'CHECKER|' .
        'PETUGAS|' .
        'OPERATIONAL AREA MANAGER|' .
        'AREA MANAGER|' .
        'NAMA FILE|' .
        'JENIS PELANGGARAN|' .
        'URAIAN KRONOLOGI|' .
        'LOKASI KEJADIAN|' .
        'HASIL PEMERIKSAAN|' .
        'EVIDENCE' .
        ')(?:\s|$)/u',
        $key
    ) === 1;
}

    private function prepareText(
        ?string $value
    ): string {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

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
            '/\n/u',
            $value
        );

        if (!is_array($lines)) {
            return trim($value);
        }

        $lines = array_map(
            fn ($line) => rtrim(
                (string) $line
            ),
            $lines
        );

        return trim(
            implode("\n", $lines)
        );
    }

    private function normalizeKey(
        mixed $value
    ): string {
        $value = Str::ascii(
            trim((string) $value)
        );

        $value = preg_replace(
            '/[^A-Za-z0-9]+/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim((string) $value)
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }
}