<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_sheets' => [
    'credentials' => env(
        'GOOGLE_SHEETS_CREDENTIALS',
        'storage/app/google/service-account.json'
        ),
    ],

    'k3061' => [
        'spreadsheet_id' => env(
            'K3061_SPREADSHEET_ID'
        ),

        'sheet_name' => env(
            'K3061_SHEET_NAME',
            'K3-06.1 Daily'
        ),
    ],




    /*
    |--------------------------------------------------------------------------
    | Google Workspace Master Fleet
    |--------------------------------------------------------------------------
    |
    | Integrasi ini memakai OAuth akun Google pengguna, bukan service account.
    | Dengan demikian folder Google Drive dapat tetap Restricted/private dan
    | folder atau spreadsheet baru dimiliki oleh akun Google yang terhubung.
    |
    */

    'google_workspace' => [
        'client_id' => env(
            'GOOGLE_WORKSPACE_CLIENT_ID'
        ),

        'client_secret' => env(
            'GOOGLE_WORKSPACE_CLIENT_SECRET'
        ),

        'redirect_uri' => env(
            'GOOGLE_WORKSPACE_REDIRECT_URI',
            'http://127.0.0.1:8001/master-fleet/google-workspace/callback'
        ),

        'source_spreadsheet_id' => env(
            'MASTER_FLEET_SOURCE_SPREADSHEET_ID',
            '1QzcN8SPbgRwHrTyJ40keGBessAnZYOrX8T7aaytmigM'
        ),

        'source_sheet_name' => env(
            'MASTER_FLEET_SOURCE_SHEET_NAME',
            'SIMOLA_PC_SET'
        ),

        'k302_template_spreadsheet_id' => env(
            'K302_TEMPLATE_SPREADSHEET_ID',
            '1QzcN8SPbgRwHrTyJ40keGBessAnZYOrX8T7aaytmigM'
        ),

        'k302_root_folder_id' => env(
            'K302_ROOT_FOLDER_ID',
            '1-qefjhFTpFaEXpshwKMaAusnYU1jpcwu'
        ),

        'evidence_root_folder_id' => env(
            'EVIDENCE_ROOT_FOLDER_ID',
            '1SyltEO_x1DKLaTG6sqABNz1xQ22dqje1'
        ),

        'k302_sheet_name' => env(
            'K302_SHEET_NAME',
            'K3-02.2'
        ),

        'k302_nopol_start_cell' => env(
            'K302_NOPOL_START_CELL',
            'C25'
        ),

        'k302_tlpg_start_cell' => env(
            'K302_TLPG_START_CELL',
            'F25'
        ),

        'k302_pc_start_cell' => env(
            'K302_PC_START_CELL',
            'AE25'
        ),

        'k302_report_date_cell' => env(
            'K302_REPORT_DATE_CELL',
            'U6'
        ),

        'k302_document_date_cell' => env(
            'K302_DOCUMENT_DATE_CELL',
            'E6'
        ),

        'k302_document_suffix_cell' => env(
            'K302_DOCUMENT_SUFFIX_CELL',
            'F6'
        ),

        'k302_daily_file_prefix' => env(
            'K302_DAILY_FILE_PREFIX',
            'K3-02.2 HARIAN OPERATOR -'
        ),
    ],

];
