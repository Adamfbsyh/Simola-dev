<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIMOLA - Generator Error Log Bulanan
    |--------------------------------------------------------------------------
    |
    | Root dapat diisi khusus melalui ERRORLOG_MONTHLY_ROOT_FOLDER_ID. Jika
    | kosong, generator otomatis memakai K302_ROOT_FOLDER_ID yang sudah ada.
    |
    */

    'template_spreadsheet_id' => env(
        'ERRORLOG_MONTHLY_TEMPLATE_SPREADSHEET_ID',
        '1EBCAR5r1eTGjujD591Wfz-HqkrAYqQRsjeetoZj0zYE'
    ),

    'root_folder_id' => env('ERRORLOG_MONTHLY_ROOT_FOLDER_ID')
        ?: env('K302_ROOT_FOLDER_ID', ''),

    'file_prefix' => env(
        'ERRORLOG_MONTHLY_FILE_PREFIX',
        'ERROR LOG SIMOLA'
    ),

    'rekap_sheet' => env(
        'ERRORLOG_MONTHLY_REKAP_SHEET',
        'REKAP'
    ),

    'period_cell' => env(
        'ERRORLOG_MONTHLY_PERIOD_CELL',
        'B1'
    ),

    // Log audit ringan berbentuk JSONL pada disk local Laravel.
    'activity_log_path' => 'simola/errorlog-monthly/activity.jsonl',
    'activity_log_max' => 500,
    'activity_ui_limit' => 8,
];
