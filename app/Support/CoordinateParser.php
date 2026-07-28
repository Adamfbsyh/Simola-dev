<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class CoordinateParser
{
    /**
     * Membaca koordinat dari berbagai format.
     *
     * Format yang didukung:
     *
     * -7.203807110524606, 112.71950675497641
     *
     * -7,2038071; 112,7195068
     *
     * 7°12'13.7"S 112°43'10.2"E
     *
     * https://www.google.com/maps/@-7.2038071,112.7195068,17z
     */
    public static function parse(
        ?string $value
    ): array {
        $value = trim(
            (string) $value
        );

        if ($value === '') {
            return [
                'latitude' => null,
                'longitude' => null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisasi tanda minus
        |--------------------------------------------------------------------------
        */

        $value = str_replace(
            [
                '−',
                '–',
                '—',
            ],
            '-',
            $value
        );

        /*
        |--------------------------------------------------------------------------
        | Format derajat, menit, detik
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | 7°12'13.7"S 112°43'10.2"E
        |
        */

        $dmsCoordinates =
            self::parseDmsCoordinates(
                $value
            );

        if ($dmsCoordinates !== null) {
            return self::validateAndRound(
                $dmsCoordinates['latitude'],
                $dmsCoordinates['longitude']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format koma desimal dengan pemisah titik koma
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | -7,2038071; 112,7195068
        |
        */

        if (
            str_contains(
                $value,
                ';'
            )
        ) {
            $parts = explode(
                ';',
                $value,
                2
            );

            if (count($parts) === 2) {
                $latitudeRaw =
                    str_replace(
                        ',',
                        '.',
                        trim($parts[0])
                    );

                $longitudeRaw =
                    str_replace(
                        ',',
                        '.',
                        trim($parts[1])
                    );

                if (
                    is_numeric($latitudeRaw)
                    &&
                    is_numeric($longitudeRaw)
                ) {
                    return self::validateAndRound(
                        (float) $latitudeRaw,
                        (float) $longitudeRaw
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Format desimal standar atau URL Google Maps
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | -7.2038071, 112.7195068
        |
        | Regex juga dapat mengambil koordinat dari URL Google Maps.
        |
        */

        if (
            preg_match(
                '/(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)/',
                $value,
                $matches
            )
        ) {
            return self::validateAndRound(
                (float) $matches[1],
                (float) $matches[2]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Format dipisahkan spasi
        |--------------------------------------------------------------------------
        |
        | Contoh:
        | -7.2038071 112.7195068
        |
        */

        if (
            preg_match(
                '/^(-?\d{1,2}(?:\.\d+)?)\s+(-?\d{1,3}(?:\.\d+)?)$/',
                $value,
                $matches
            )
        ) {
            return self::validateAndRound(
                (float) $matches[1],
                (float) $matches[2]
            );
        }

        throw ValidationException::withMessages([
            'coordinates' =>
                'Format koordinat tidak valid. Gunakan format Latitude, Longitude. Contoh: -7.2038071, 112.7195068',
        ]);
    }

    /**
     * Mengubah koordinat menjadi format tampilan.
     */
    public static function format(
        mixed $latitude,
        mixed $longitude
    ): string {
        if (
            $latitude === null
            ||
            $longitude === null
            ||
            $latitude === ''
            ||
            $longitude === ''
        ) {
            return '';
        }

        return sprintf(
            '%.7F, %.7F',
            (float) $latitude,
            (float) $longitude
        );
    }

    /**
     * Memproses koordinat berformat DMS.
     */
    private static function parseDmsCoordinates(
        string $value
    ): ?array {
        $pattern =
            '/
                (\d{1,2})\s*[°º]\s*
                (\d{1,2})\s*[\'’′]\s*
                (\d+(?:\.\d+)?)\s*["”″]?\s*
                ([NS])
                \s*[,;\s]+\s*
                (\d{1,3})\s*[°º]\s*
                (\d{1,2})\s*[\'’′]\s*
                (\d+(?:\.\d+)?)\s*["”″]?\s*
                ([EW])
            /ix';

        if (
            !preg_match(
                $pattern,
                $value,
                $matches
            )
        ) {
            return null;
        }

        $latitude =
            self::dmsToDecimal(
                degrees: (float) $matches[1],
                minutes: (float) $matches[2],
                seconds: (float) $matches[3],
                direction: strtoupper(
                    $matches[4]
                )
            );

        $longitude =
            self::dmsToDecimal(
                degrees: (float) $matches[5],
                minutes: (float) $matches[6],
                seconds: (float) $matches[7],
                direction: strtoupper(
                    $matches[8]
                )
            );

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Mengubah DMS menjadi decimal degree.
     */
    private static function dmsToDecimal(
        float $degrees,
        float $minutes,
        float $seconds,
        string $direction
    ): float {
        $decimal =
            $degrees
            +
            ($minutes / 60)
            +
            ($seconds / 3600);

        if (
            in_array(
                $direction,
                [
                    'S',
                    'W',
                ],
                true
            )
        ) {
            $decimal *= -1;
        }

        return $decimal;
    }

    /**
     * Memastikan latitude dan longitude valid,
     * kemudian membulatkannya menjadi tujuh desimal.
     */
    private static function validateAndRound(
        float $latitude,
        float $longitude
    ): array {
        if (
            $latitude < -90
            ||
            $latitude > 90
        ) {
            throw ValidationException::withMessages([
                'coordinates' =>
                    'Latitude harus berada antara -90 sampai 90.',
            ]);
        }

        if (
            $longitude < -180
            ||
            $longitude > 180
        ) {
            throw ValidationException::withMessages([
                'coordinates' =>
                    'Longitude harus berada antara -180 sampai 180.',
            ]);
        }

        return [
            'latitude' =>
                round(
                    $latitude,
                    7
                ),

            'longitude' =>
                round(
                    $longitude,
                    7
                ),
        ];
    }
}