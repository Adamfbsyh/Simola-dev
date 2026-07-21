<?php

namespace App\Services;

use App\Models\MonitoringEvent;
use Illuminate\Support\Str;

class MonitoringAmtSyncService
{
    private const EVENT_TYPES = [
        'pelanggaran',
        'kendala',
        'accident',
    ];

    public function __construct(
        private readonly AmtNameExtractor $amtNameExtractor,
        private readonly PdfTextReaderService $pdfTextReader
    ) {
    }

    /**
     * Memeriksa dan memperbaiki nama AMT satu event.
     */
    public function sync(
        MonitoringEvent $event,
        bool $write = false
    ): array {
        $eventType = mb_strtolower(
            trim(
                (string) $event->event_type
            ),
            'UTF-8'
        );

        if (
            !in_array(
                $eventType,
                self::EVENT_TYPES,
                true
            )
        ) {
            return [
                'event_id' => $event->id,
                'event_type' => $eventType,
                'before' => $event->driver_name,
                'after' => $event->driver_name,
                'source' => 'ignored_event_type',
                'changed' => false,
                'text_available' => false,
            ];
        }

        $event->loadMissing(
            'reportUpload'
        );

        $before = $this->normalizeDisplayName(
            $event->driver_name
        );

        $text = $this->pdfTextReader
            ->readForEvent($event);

        $extractedAmt = $this->amtNameExtractor
            ->extract(
                $text,
                $event->nopol
            );

        $existingIsInvalid =
            $this->isInvalidExistingName(
                $before,
                $event->raw_data
            );

        /*
        |--------------------------------------------------------------------------
        | Penentuan nama yang dipakai
        |--------------------------------------------------------------------------
        |
        | 1. Nama AMT yang ditemukan dari label PDF selalu diprioritaskan.
        | 2. Nama lama dipertahankan jika bukan header/Lead/SPV.
        | 3. Nama header atau nama jabatan dikosongkan.
        |
        */

        if ($extractedAmt !== null) {
            $after = $this->normalizeDisplayName(
                $extractedAmt
            );

            $source = 'pdf_amt_label';
        } elseif (
            $before !== null
            &&
            !$existingIsInvalid
        ) {
            $after = $before;

            $source = 'existing_driver_name';
        } else {
            $after = null;

            $source = $text !== ''
                ? 'amt_not_found'
                : 'pdf_text_empty';
        }

        $beforeComparable = $this->normalizeKey(
            $before
        );

        $afterComparable = $this->normalizeKey(
            $after
        );

        $changed =
            $beforeComparable
            !== $afterComparable;

        /*
         * Standarisasi kapital juga dianggap perubahan.
         */
        if (
            !$changed
            &&
            trim((string) $event->driver_name)
            !== trim((string) $after)
        ) {
            $changed = true;
        }

        if (
            $write
            &&
            $changed
        ) {
            $event->forceFill([
                'driver_name' => $after,
            ]);

            /*
             * Mencegah Observer terpanggil berulang.
             */
            $event->saveQuietly();
        }

        return [
            'event_id' =>
                $event->id,

            'event_type' =>
                $eventType,

            'nopol' =>
                $event->nopol,

            'file_name' =>
                $event->reportUpload?->nama_file,

            'before' =>
                $before,

            'after' =>
                $after,

            'source' =>
                $source,

            'changed' =>
                $changed,

            'text_available' =>
                $text !== '',
        ];
    }

    /**
     * Memastikan nama lama bukan header atau nama jabatan lain.
     */
    private function isInvalidExistingName(
        ?string $name,
        mixed $rawData
    ): bool {
        $key = $this->normalizeKey(
            $name
        );

        if ($key === '') {
            return true;
        }

        $invalidExact = [
            'LAPORAN',
            'LAPORAN AMT',
            'LAPORAN PELANGGARAN',
            'LAPORAN PELANGGARAN AMT',
            'LAPORAN KENDALA',
            'LAPORAN ACCIDENT',
            'LAPORAN KECELAKAAN',

            'PELANGGARAN',
            'KENDALA',
            'ACCIDENT',
            'KECELAKAAN',
            'INSIDEN',
            'KEJADIAN',
        
            'MT',
            'MT NOPOL',
            'MT NO POL',
            'NOPOL',
            'NO POL',
            'NOMOR POLISI',

            'AMT',
            'NAMA AMT',
            'DRIVER',
            'NAMA DRIVER',
            'PENGEMUDI',
            'NAMA PENGEMUDI',
            'AWAK MOBIL TANGKI',

            'LEAD',
            'LEADER',
            'NAMA LEAD',
            'NAMA LEADER',
            'TEAM LEAD',
            'TEAM LEADER',

            'SPV',
            'SUPERVISOR',
            'NAMA SPV',
            'NAMA SUPERVISOR',

            'CHECKER',
            'PETUGAS',
            'ADMIN',
            'SECURITY',

            'TLPG',
            'TERMINAL',
            'TERMINALS',

            'NO',
            'TOTAL',
            'JUMLAH',
            'JABATAN',
        ];

        if (
            in_array(
                $key,
                $invalidExact,
                true
            )
        ) {
            return true;
        }

        /*
         * Tolak jika nama berisi jabatan non-AMT.
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
                'LEAD|' .
                'LEADER|' .
                'SPV|' .
                'SUPERVISOR|' .
                'CHECKER|' .
                'PETUGAS|' .
                'ADMIN|' .
                'SECURITY|' .
                'MANAGER|' .
                'PENGAWAS|' .
                'KEPALA' .
                ')\b/',
                $key
            )
        ) {
            return true;
        }

        /*
         * Tolak jika driver_name sama dengan nilai field Lead/SPV
         * yang tersimpan pada raw_data.
         */
        $roleNames = [];

        $this->collectRoleNames(
            $rawData,
            $roleNames
        );

        foreach ($roleNames as $roleName) {
            if (
                $this->normalizeKey($roleName)
                === $key
            ) {
                return true;
            }
        }

        /*
         * Tolak NOPOL yang salah terbaca sebagai nama.
         */
        if (
            preg_match(
                '/^[A-Z]{1,2}\s*\d{1,4}\s*[A-Z]{0,3}$/',
                $key
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Mengambil nama Lead/SPV dari raw_data.
     */
    private function collectRoleNames(
        mixed $rawData,
        array &$roleNames
    ): void {
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
                return;
            }
        }

        if (is_object($rawData)) {
            $rawData = (array) $rawData;
        }

        if (!is_array($rawData)) {
            return;
        }

        foreach ($rawData as $key => $value) {
            $normalizedKey = $this->normalizeKey(
                $key
            );

            $isRoleKey = preg_match(
                '/\b(?:LEAD|LEADER|SPV|SUPERVISOR|' .
                'CHECKER|PETUGAS|PENGAWAS|MANAGER)\b/',
                $normalizedKey
            ) === 1;

            if (
                $isRoleKey
                &&
                is_scalar($value)
                &&
                trim((string) $value) !== ''
            ) {
                $roleNames[] = (string) $value;
            }

            if (
                is_array($value)
                ||
                is_object($value)
            ) {
                $this->collectRoleNames(
                    $value,
                    $roleNames
                );
            }
        }
    }

    private function normalizeDisplayName(
        mixed $value
    ): ?string {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return null;
        }

        $value = preg_replace(
            '/^(?:NAMA\s*)?(?:AMT|DRIVER|' .
            'PENGEMUDI|AWAK\s+MOBIL\s+TANGKI)' .
            '\s*[:\-]?\s*/iu',
            '',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        if ($value === '') {
            return null;
        }

        return mb_strtoupper(
            $value,
            'UTF-8'
        );
    }

    private function normalizeKey(
        mixed $value
    ): string {
        $value = Str::ascii(
            trim(
                (string) $value
            )
        );

        $value = preg_replace(
            '/[^A-Za-z0-9]+/',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            trim(
                (string) $value
            )
        );

        return mb_strtoupper(
            (string) $value,
            'UTF-8'
        );
    }
}