<?php

namespace App\Services;

use Illuminate\Support\Str;

class PelanggaranCategoryService
{
    /**
     * Nama resmi sesuai Form K3.2 Daily.
     */
    public function master(): array
    {
        return [
            'Menerima Penumpang Selain AMT',
            'Mengemudi Lebih dari 4 Jam',
            'Over Speed',
            'Perlambatan Mendadak',
            'Akselerasi Mendadak',
            'Tikungan Tajam',
            'Melebihi Batas Waktu Parkir',
            'Seat Belt',
            'Keluar Rute',
            'Berganti AMT Tanpa Lisensi',
            'Menggunakan Handphone / Gadget',
            'Merokok / Vape',
            'Menutup / Mengubah Posisi CAM',
            'Merusak / Melepas Device (GPS / CAM)',
            'Pengurangan Bahan Bakar',
            'Berganti AMT Yang Tidak Berlisensi (Accident)',
            'Pengemudi Kelelahan (Accident)',
            'Mengemudi Tidak Baik (Napza / Alkohol)',
            'Menghilangkan Sinyal GPS (Jammer)',
            'Geolokasi (Blackzone & Redzone)',
            'Pelecehan Verbal',
            'Mengintervensi, Mengancam / Bekerja Sama Dengan Petugas RTC',
        ];
    }

    /**
     * Mengubah nama PDF atau K3.2 menjadi nama resmi K3.2.
     */
    public function canonicalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $text = $this->normalizeText($value);

        /*
        |--------------------------------------------------------------------------
        | Aturan kategori
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\b(?:MENERIMA|MEMBAWA)\s+PENUMPANG\s+SELAIN\s+AMT\b/',
                $text
            )
        ) {
            return 'Menerima Penumpang Selain AMT';
        }

        if (
            preg_match(
                '/\b(?:MENGEMUDI|BERKENDARA).*' .
                '(?:LEBIH\s+DARI|DI\s*ATAS|DIATAS).*4\s*JAM\b/',
                $text
            ) ||
            str_contains($text, 'MENGEMUDI 4 JAM')
        ) {
            return 'Mengemudi Lebih dari 4 Jam';
        }

        if (
            preg_match(
                '/\bOVER\s*SPEED\b|\bOVERSPEED\b|' .
                '\bKECEPATAN\s+BERLEBIH\b/',
                $text
            )
        ) {
            return 'Over Speed';
        }

        if (
            str_contains($text, 'PERLAMBATAN MENDADAK') ||
            str_contains($text, 'HARSH BRAKING') ||
            str_contains($text, 'HARD BRAKING')
        ) {
            return 'Perlambatan Mendadak';
        }

        if (
            str_contains($text, 'AKSELERASI MENDADAK') ||
            str_contains($text, 'RAPID ACCELERATION') ||
            str_contains($text, 'HARSH ACCELERATION')
        ) {
            return 'Akselerasi Mendadak';
        }

        if (
            str_contains($text, 'TIKUNGAN TAJAM') ||
            str_contains($text, 'SHARP TURN') ||
            str_contains($text, 'HARSH CORNERING')
        ) {
            return 'Tikungan Tajam';
        }

        if (
            preg_match(
                '/\bMELEBIHI\s+BATAS\s+WAKTU' .
                '(?:\s+PARKIR)?\b/',
                $text
            ) ||
            str_contains($text, 'BATAS WAKTU PARKIR')
        ) {
            return 'Melebihi Batas Waktu Parkir';
        }

        if (
            str_contains($text, 'SEAT BELT') ||
            str_contains($text, 'SEATBELT') ||
            str_contains($text, 'SABUK PENGAMAN')
        ) {
            return 'Seat Belt';
        }

        if (
            str_contains($text, 'KELUAR RUTE') ||
            str_contains($text, 'OFF ROUTE') ||
            str_contains($text, 'PENYIMPANGAN RUTE')
        ) {
            return 'Keluar Rute';
        }

        /*
         * Accident diperiksa sebelum pergantian AMT biasa.
         */
        if (
            preg_match(
                '/\b(?:BERGANTI|PERGANTIAN|GANTI)\s+AMT\b/',
                $text
            ) &&
            preg_match(
                '/\b(?:TIDAK\s+BERLISENSI|TANPA\s+LISENSI)\b/',
                $text
            ) &&
            preg_match(
                '/\b(?:ACCIDENT|LAKA|KECELAKAAN)\b/',
                $text
            )
        ) {
            return 'Berganti AMT Yang Tidak Berlisensi (Accident)';
        }

        if (
            preg_match(
                '/\b(?:BERGANTI|PERGANTIAN|GANTI)\s+AMT\b/',
                $text
            ) &&
            preg_match(
                '/\b(?:TIDAK\s+BERLISENSI|TANPA\s+LISENSI)\b/',
                $text
            )
        ) {
            return 'Berganti AMT Tanpa Lisensi';
        }

        if (
            preg_match(
                '/\b(?:HANDPHONE|HAND\s*PHONE|GADGET|' .
                'PONSEL|TELEPON|HP)\b/',
                $text
            )
        ) {
            return 'Menggunakan Handphone / Gadget';
        }

        if (
            preg_match(
                '/\b(?:MEROKOK|ROKOK|VAPE|VAPING)\b/',
                $text
            )
        ) {
            return 'Merokok / Vape';
        }

        /*
         * Menutup/mengubah posisi kamera.
         *
         * Mencakup:
         * Menutup Atau Mengubah Posisi Cam
         * Menutup / Mengubah Posisi CAM
         * Menggeser Kamera
         */
        if (
            preg_match(
                '/\b(?:MENUTUP|TERTUTUP|MENGUBAH|MERUBAH|' .
                'MEMINDAH|MENGGESER|GESER)\b.{0,60}' .
                '\b(?:CAM|CAMERA|KAMERA)\b/',
                $text
            )
        ) {
            return 'Menutup / Mengubah Posisi CAM';
        }

        /*
         * Jammer diperiksa sebelum kerusakan device.
         */
        if (
            str_contains($text, 'JAMMER') ||
            str_contains($text, 'JAMMING') ||
            str_contains($text, 'MENGHILANGKAN SINYAL GPS')
        ) {
            return 'Menghilangkan Sinyal GPS (Jammer)';
        }

        if (
            preg_match(
                '/\b(?:MERUSAK|MELEPAS|MENCABUT|MEMUTUS)\b' .
                '.{0,60}\b(?:DEVICE|GPS|CAM|CAMERA|KAMERA)\b/',
                $text
            )
        ) {
            return 'Merusak / Melepas Device (GPS / CAM)';
        }

        if (
            str_contains($text, 'PENGURANGAN BAHAN BAKAR') ||
            str_contains($text, 'PENGURANGAN BBM') ||
            str_contains($text, 'PENCURIAN BBM') ||
            str_contains($text, 'FUEL THEFT')
        ) {
            return 'Pengurangan Bahan Bakar';
        }

        if (
            preg_match(
                '/\b(?:KELELAHAN|MENGANTUK|MICROSLEEP|FATIGUE)\b/',
                $text
            )
        ) {
            return 'Pengemudi Kelelahan (Accident)';
        }

        if (
            preg_match(
                '/\b(?:NAPZA|NARKOBA|ALKOHOL|MABUK|' .
                'OBAT\s+TERLARANG)\b/',
                $text
            )
        ) {
            return 'Mengemudi Tidak Baik (Napza / Alkohol)';
        }

        if (
            preg_match(
                '/\b(?:BLACK\s*ZONE|BLACKZONE|RED\s*ZONE|' .
                'REDZONE|GEOLOKASI)\b/',
                $text
            )
        ) {
            return 'Geolokasi (Blackzone & Redzone)';
        }

        if (
            str_contains($text, 'PELECEHAN VERBAL') ||
            str_contains($text, 'KEKERASAN VERBAL') ||
            str_contains($text, 'VERBAL ABUSE')
        ) {
            return 'Pelecehan Verbal';
        }

        if (
            preg_match(
                '/\b(?:INTERVENSI|MENGINTERVENSI|' .
                'MENGANCAM|ANCAMAN)\b/',
                $text
            ) ||
            preg_match(
                '/\bBEKERJA\s+SAMA\b.{0,50}' .
                '\bPETUGAS\s+RTC\b/',
                $text
            )
        ) {
            return 'Mengintervensi, Mengancam / Bekerja Sama Dengan Petugas RTC';
        }

        /*
        |--------------------------------------------------------------------------
        | Fuzzy matching
        |--------------------------------------------------------------------------
        |
        | Dipakai jika kalimat tidak persis tetapi masih sangat mirip.
        */
        return $this->findClosestMaster($value);
    }

    private function findClosestMaster(
        string $value
    ): ?string {
        $source = $this->normalizeForSimilarity(
            $value
        );

        if ($source === '') {
            return null;
        }

        $bestCategory = null;
        $bestScore = 0.0;

        foreach ($this->master() as $category) {
            $target = $this->normalizeForSimilarity(
                $category
            );

            $similarPercentage = 0.0;

            similar_text(
                $source,
                $target,
                $similarPercentage
            );

            $maxLength = max(
                strlen($source),
                strlen($target)
            );

            if ($maxLength === 0) {
                continue;
            }

            $distance = levenshtein(
                $source,
                $target
            );

            $levenshteinPercentage =
                max(
                    0,
                    (
                        1 -
                        ($distance / $maxLength)
                    ) * 100
                );

            $score = max(
                $similarPercentage,
                $levenshteinPercentage
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $category;
            }
        }

        /*
         * Jangan dipaksakan jika terlalu berbeda.
         */
        return $bestScore >= 70
            ? $bestCategory
            : null;
    }

    private function normalizeText(
        string $value
    ): string {
        $value = strtoupper(
            Str::ascii($value)
        );

        $replacements = [
            'CAMERA' => 'CAM',
            'KAMERA' => 'CAM',
            'HAND PHONE' => 'HANDPHONE',
            'PONSEL' => 'HANDPHONE',
            'TELEPON' => 'HANDPHONE',
            'SABUK PENGAMAN' => 'SEAT BELT',
            'OVERSPEED' => 'OVER SPEED',
        ];

        $value = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $value
        );

        $value = preg_replace(
            '/[^A-Z0-9]+/',
            ' ',
            $value
        );

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $value
            )
        );
    }

    private function normalizeForSimilarity(
        string $value
    ): string {
        $value = $this->normalizeText(
            $value
        );

        /*
         * Kata penghubung tidak memengaruhi kategori.
         *
         * Contoh:
         * Menutup ATAU Mengubah Posisi CAM
         * Menutup / Mengubah Posisi CAM
         */
        $value = preg_replace(
            '/\b(?:DAN|ATAU|SERTA)\b/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim($value)
        );

        return str_replace(
            ' ',
            '',
            $value
        );
    }
}