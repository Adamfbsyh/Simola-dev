<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status fitur Master Fleet
    |--------------------------------------------------------------------------
    |
    | Production dapat mempertahankan nilai false sampai fitur selesai diuji.
    | Development menggunakan true.
    |
    */

    'enabled' => env(
        'FEATURE_MASTER_FLEET',
        false
    ),

    /*
    |--------------------------------------------------------------------------
    | Jumlah PC Operator
    |--------------------------------------------------------------------------
    */

    'operator_count' => (int) env(
        'MASTER_FLEET_OPERATOR_COUNT',
        12
    ),

    /*
    |--------------------------------------------------------------------------
    | Kategori jarak
    |--------------------------------------------------------------------------
    |
    | Batas jarak final akan disesuaikan dengan aturan operasional Anda.
    | Nilai ini masih berupa konfigurasi awal.
    |
    */

    'distance_categories' => [
        'dekat' => [
            'label' => 'Dekat',
            'weight' => 1,
        ],

        'sedang' => [
            'label' => 'Sedang',
            'weight' => 2,
        ],

        'jauh' => [
            'label' => 'Jauh',
            'weight' => 3,
        ],
    ],

    'distance' => [
    /*
     * Sesuaikan batas ini dengan aturan spreadsheet operasional.
     *
     * Nilai sementara:
     * 0–100 km   = dekat
     * >100–170   = sedang
     * >170 km    = jauh
     */

    'near_max_km' => (float) env(
        'MASTER_FLEET_NEAR_MAX_KM',
        100
    ),

    'medium_max_km' => (float) env(
        'MASTER_FLEET_MEDIUM_MAX_KM',
        170
    ),

    'weights' => [
        'near' => 1,
        'medium' => 2,
        'far' => 3,
        'unknown' => 1,
    ],
    ],
];