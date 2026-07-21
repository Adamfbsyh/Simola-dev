<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>{{ $reportTitle }} - {{ $periodLabel }}</title>

    <style>
        @page {
            size: 297mm 594mm;
            margin: 4mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #e5e7eb;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
        }

        /* =========================================================
           TOOLBAR PREVIEW
           ========================================================= */

        .preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 11px 18px;
            border-bottom: 1px solid #d1d5db;
            background: #ffffff;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.12);
        }

        .preview-toolbar-title strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
        }

        .preview-toolbar-title span {
            display: block;
            margin-top: 3px;
            color: #6b7280;
            font-size: 11px;
        }

        .preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .preview-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 8px 13px;
            border: none;
            border-radius: 7px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }

        .preview-print {
            background: #2563eb;
        }

        .preview-stream {
            background: #0f766e;
        }

        .preview-download {
            background: #16a34a;
        }

        /* =========================================================
           HALAMAN LAPORAN
           ========================================================= */

        .report-page {
            width: 289mm;
            max-width: 289mm;
            margin: 14px auto;
            padding: 0;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.16);

            /*
            * Tinggi area cetak:
            * 594 mm dikurangi margin atas dan bawah 8 mm.
            */
            min-height: 586mm;

            page-break-before: auto;
            page-break-after: always;
            page-break-inside: avoid;
        }

        .report-page:last-child {
            page-break-after: auto;
        }

        /* =========================================================
           HEADER UTAMA
           ========================================================= */

        .header-main {
            width: 100%;
            height: 22mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-main > tbody > tr > td {
            height: 22mm;
            border: 1px solid #000000;
            vertical-align: middle;
        }

        .header-logo {
            width: 27%;
            padding: 2mm 4mm;
        }

        .header-logo img {
            display: block;
            width: auto;
            max-width: 43mm;
            max-height: 13mm;
        }

        .header-logo-fallback {
            color: #006b4f;
            font-size: 14px;
            font-weight: 900;
        }

        .header-title {
            width: 48%;
            padding: 1mm 2mm;
            text-align: center;
        }

        .header-title-main {
            color: #000000;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.12;
            letter-spacing: 0.1px;
        }

        .header-title-sub {
            margin-top: 2px;
            color: #000000;
            font-size: 6px;
            font-style: italic;
            line-height: 1.1;
            text-decoration: underline;
        }

        .header-right {
            width: 25%;
            padding: 0 !important;
        }

        .header-right-table {
            width: 100%;
            height: 21mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-right-table td {
            border: none !important;
            vertical-align: middle;
        }

        .form-code-cell {
            width: 72%;
            padding: 1mm 1mm 1mm 2mm !important;
            text-align: right;
        }

        .k3-logo-cell {
            width: 28%;
            padding: 1mm 2mm 1mm 1mm !important;
            text-align: center;
        }

        .form-code-box {
            display: inline-block;
            min-width: 33mm;
            padding: 1.8mm 2mm;
            border: 1px solid #000000;
            background: #d9d9d9;
            color: #000000;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
        }

        .k3-logo-image {
            display: inline-block;
            width: auto;
            height: 12mm;
            max-width: 17mm;
            max-height: 12mm;
        }

        .k3-logo-fallback {
            display: inline-block;
            width: 12mm;
            height: 12mm;
            padding-top: 3.4mm;
            border: 2px solid #15803d;
            border-radius: 50%;
            color: #15803d;
            font-size: 6px;
            font-weight: 900;
            text-align: center;
        }

        /* =========================================================
           INFORMASI FORM
           ========================================================= */

        .header-information {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-information > tbody > tr > td {
            border: 1px solid #000000;
            vertical-align: middle;
        }

        .info-left {
            width: 62%;
            padding: 0.8mm 1.5mm;
        }

        .info-right {
            width: 30%;
            padding: 0.8mm 1.5mm;
        }

        .info-page {
            width: 8%;
            padding: 0;
            text-align: center;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            height: 3.5mm;
            padding: 0;
            border: none !important;
            font-size: 5.8px;
        }

        .info-label {
            width: 25mm;
            font-weight: 900;
            white-space: nowrap;
        }

        .info-label-small {
            width: 18mm;
            font-weight: 900;
            white-space: nowrap;
        }

        .info-colon {
            width: 3mm;
            text-align: center;
            font-weight: 900;
        }

        .info-value {
            overflow: hidden;
            padding: 0 0.8mm 0.3mm !important;
            border-bottom: 1px dotted #000000 !important;
            text-align: left;
            white-space: nowrap;
        }

        .page-number-label {
            display: block;
            margin-bottom: 1px;
            font-size: 6px;
            font-weight: 900;
        }

        .page-number-value {
            display: block;
            font-size: 7px;
            font-weight: 900;
        }

        /* =========================================================
           PERSETUJUAN DAN TANDA TANGAN
           ========================================================= */

        .approval-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .approval-table th,
        .approval-table td {
            border: 1px solid #000000;
            padding: 1px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .topic-title {
            height: 4mm;
            font-size: 7px;
            font-weight: 900;
        }

        .approval-group-title {
            height: 3.8mm;
            padding-left: 0.8mm !important;
            overflow: hidden;
            font-size: 5.4px;
            font-weight: 700;
            text-align: left !important;
            white-space: nowrap;
        }

        .approval-role {
            height: 4mm;
            overflow: hidden;
            font-size: 5.2px;
            white-space: nowrap;
        }

        .approval-signature {
            height: 17mm !important;
            padding: 0 !important;
            overflow: hidden !important;
            vertical-align: middle !important;
        }

        .signature-box {
            width: 100%;
            height: 16mm;
            overflow: hidden;
            text-align: center;
            white-space: nowrap;
        }

        .signature-box img {
            display: inline-block !important;
            width: auto !important;
            height: 14mm !important;
            max-width: 24mm !important;
            max-height: 14mm !important;
            margin: 1mm auto 0 !important;
        }

        .signature-empty {
            display: inline-block;
            width: 100%;
            height: 14mm;
        }

        .approval-name {
            height: 4mm;
            overflow: hidden;
            font-size: 5.2px;
            font-weight: 700;
            white-space: nowrap;
        }

        .approval-note {
            height: 4.7mm;
            padding: 0.6mm 1mm !important;
            font-size: 5.4px;
            font-weight: 900;
        }

        /* =========================================================
           TABEL DATA
           ========================================================= */

        .data-table {
            width: 289mm;
            max-width: 289mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000000;
            overflow: hidden;
            text-align: center;
            vertical-align: middle;
        }

        .data-table thead {
            display: table-row-group;
        }

        .data-table tbody tr {
            page-break-inside: avoid;
        }

        .main-header-cell {
            padding: 0.5mm 0.15mm;
            overflow: hidden;
            font-size: 6.2px;
            font-weight: 900;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
        }

        .group-header {
            height: 5mm;
            padding: 0.6mm 0;
            font-size: 7px;
            font-weight: 900;
            line-height: 1;
        }

        .category-header {
            height: 59mm;
            padding: 0 !important;
            overflow: hidden;
            vertical-align: bottom !important;
        }

        .category-rotate {
            position: relative;
            width: 100%;
            height: 59mm;
            overflow: hidden;
        }

        .category-text {
            position: absolute;
            top: 50%;
            left: 50%;
            display: block;
            width: 54mm;
            overflow: hidden;
            color: #000000;
            font-size: 7.3px;
            font-weight: 800;
            line-height: 1.03;
            text-align: center;
            white-space: nowrap;
            transform: translate(-50%, -50%) rotate(-90deg);
            transform-origin: center center;
        }

        .data-table tbody td {
            padding: 0.1mm 0.15mm;
            line-height: 1;
            white-space: nowrap;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .cell-number {
            overflow: hidden;
            padding: 0.1mm !important;
            text-align: center;
            white-space: nowrap;
        }

        .cell-nopol {
            overflow: hidden;
            padding: 0.1mm 0.25mm !important;
            font-weight: 800;
            text-align: center;
            white-space: nowrap;
        }

        .cell-tlpg {
            overflow: hidden;
            padding: 0.1mm 0.25mm !important;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .cell-driver {
            overflow: hidden;
            padding: 0.1mm 0.3mm !important;
            text-align: left !important;
            white-space: nowrap;
        }

        .value-filled {
            font-weight: 900;
        }

        .value-empty {
            color: transparent;
        }

        .header-main,
        .header-information,
        .approval-table,
        .data-table,
        .report-footer {
            page-break-inside: avoid;
        }

        /*
         * Tinggi dan font baris menyesuaikan jumlah kendaraan.
         */

        .density-normal tbody td {
            height: 4.2mm;
            font-size: 5.8px;
        }

        .density-medium tbody td {
            height: 4mm;
            font-size: 5.6px;
        }

        .density-compact tbody td {
            height: 3.7mm;
            font-size: 5.4px;
        }

        .density-dense tbody td {
            height: 3.35mm;
            font-size: 5.1px;
        }

        .density-ultra tbody td {
            height: 3mm;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            font-size: 4.9px;
        }

        /* =========================================================
           FOOTER
           ========================================================= */

        .report-footer {
            width: 100%;
            margin-top: 0.8mm;
            border-collapse: collapse;
        }

        .report-footer td {
            border: none;
            color: #4b5563;
            font-size: 4.8px;
        }

        .footer-right {
            text-align: right;
        }

        /* =========================================================
           SCREEN DAN PRINT
           ========================================================= */

        @media screen {
            body {
                overflow-x: auto;
            }

            .report-page {
                width: 289mm;
                max-width: 289mm;
                min-height: 586mm;
                margin: 14px auto;
            }
        }

        @media print {
            body {
                overflow: visible;
                background: #ffffff;
            }

            .preview-toolbar {
                display: none !important;
            }

            .report-page {
                width: 289mm;
                max-width: 289mm;
                min-height: 586mm;
                margin: 0 auto;
                padding: 0;
                overflow: hidden;
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    @php
        /*
        |--------------------------------------------------------------------------
        | Helper gambar Base64
        |--------------------------------------------------------------------------
        */

        $imageToDataUri = static function (?string $relativePath): ?string {
            if (!$relativePath) {
                return null;
            }

            $absolutePath = public_path($relativePath);

            if (
                !is_file($absolutePath) ||
                !is_readable($absolutePath)
            ) {
                return null;
            }

            $contents = file_get_contents($absolutePath);

            if ($contents === false) {
                return null;
            }

            $mime = null;

            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($absolutePath);
            }

            if (!$mime) {
                $extension = strtolower(
                    pathinfo(
                        $absolutePath,
                        PATHINFO_EXTENSION
                    )
                );

                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
            }

            return
                'data:' .
                $mime .
                ';base64,' .
                base64_encode($contents);
        };

        /*
        |--------------------------------------------------------------------------
        | Pembagian kategori
        |--------------------------------------------------------------------------
        */

        $sp1Categories = array_slice(
            $masterCategories,
            0,
            10
        );

        $sp2Categories = array_slice(
            $masterCategories,
            10,
            1
        );

        $sp3Categories = array_slice(
            $masterCategories,
            11,
            1
        );

        $returnCategories = array_slice(
            $masterCategories,
            12
        );

        /*
        |--------------------------------------------------------------------------
        | Logo
        |--------------------------------------------------------------------------
        */

        $logoData = $imageToDataUri(
            'images/k32/pertamina-patra-logistik.png'
        );

        $k3LogoData = $imageToDataUri(
            'images/k32/logo-k3.png'
        );

        /*
        |--------------------------------------------------------------------------
        | Penandatangan
        |--------------------------------------------------------------------------
        */

        $signaturePersons = [
            [
                'role' => 'Team Lead 1',
                'name' => "Muhammad Anang Ma'ruf",
                'image' => 'images/k32/sign-team-lead-1.png',
            ],
            [
                'role' => 'Team Lead 2',
                'name' => 'Wahyu Agustiawan',
                'image' => 'images/k32/sign-team-lead-2.png',
            ],
            [
                'role' => 'Team Lead 3',
                'name' => 'Nurul Al Fikhri',
                'image' => 'images/k32/sign-team-lead-3.png',
            ],
            [
                'role' => 'Team Lead 4',
                'name' => 'Betano Medi Putra',
                'image' => 'images/k32/sign-team-lead-4.png',
            ],
            [
                'role' => 'Spv RTC JATIMBALINUS',
                'name' => 'Rendi',
                'image' => 'images/k32/sign-spv-rtc.png',
            ],
            [
                'role' => 'Lead of Site Terminal',
                'name' => 'Haryo Wisnu Erdianto',
                'image' => 'images/k32/sign-lead-terminal-1.png',
            ],
            [
                'role' => 'Lead of Site Terminal',
                'name' => 'Batara Surya Kalbu H',
                'image' => 'images/k32/sign-lead-terminal-2.png',
            ],
            [
                'role' => 'Sr. Spv. Operation Fleet Jatimbalinus',
                'name' => 'Andi Yitno',
                'image' => 'images/k32/sign-sr-spv-operation.png',
            ],
        ];

        foreach ($signaturePersons as &$person) {
            $person['data_uri'] = $imageToDataUri(
                $person['image']
            );
        }

        unset($person);

        /*
        |--------------------------------------------------------------------------
        | Satu TLPG menjadi satu halaman
        |--------------------------------------------------------------------------
        */

        $reportPages = [];

        foreach ($groups as $group) {
            $reportPages[] = [
                'group' => $group,
                'rows' => $group['rows'],
            ];
        }

        $totalReportPages = count(
            $reportPages
        );
    @endphp

    @if(!$pdfMode)
        <div class="preview-toolbar">
            <div class="preview-toolbar-title">
                <strong>Preview Laporan K3.2</strong>

                <span>
                    {{ $periodLabel }}
                    ·
                    {{ $selectedTlpg !== ''
                        ? $selectedTlpg
                        : 'Semua TLPG' }}
                </span>
            </div>

            <div class="preview-actions">
                <button
                    type="button"
                    onclick="window.print()"
                    class="preview-button preview-print"
                >
                    Cetak
                </button>

                <a
                    href="{{ $streamUrl }}"
                    target="_blank"
                    class="preview-button preview-stream"
                >
                    Buka PDF
                </a>

                <a
                    href="{{ $downloadUrl }}"
                    class="preview-button preview-download"
                >
                    Unduh PDF
                </a>
            </div>
        </div>
    @endif

    @foreach($reportPages as $reportPageIndex => $reportPage)
        @php
            $group = $reportPage['group'];
            $pageRows = $reportPage['rows'];

            $currentPage = str_pad(
                (string) ($reportPageIndex + 1),
                2,
                '0',
                STR_PAD_LEFT
            );

            $totalPages = str_pad(
                (string) $totalReportPages,
                2,
                '0',
                STR_PAD_LEFT
            );

            $vehicleCount = count(
                $pageRows
            );

            if ($vehicleCount >= 76) {
                $densityClass = 'density-ultra';
            } elseif ($vehicleCount >= 61) {
                $densityClass = 'density-dense';
            } elseif ($vehicleCount >= 46) {
                $densityClass = 'density-compact';
            } elseif ($vehicleCount >= 31) {
                $densityClass = 'density-medium';
            } else {
                $densityClass = 'density-normal';
            }
        @endphp

        <section class="report-page">
            {{-- HEADER UTAMA --}}
            <table class="header-main">
                <tr>
                    <td class="header-logo">
                        @if($logoData)
                            <img
                                src="{{ $logoData }}"
                                alt="Pertamina Patra Logistik"
                            >
                        @else
                            <div class="header-logo-fallback">
                                PERTAMINA PATRA LOGISTIK
                            </div>
                        @endif
                    </td>

                    <td class="header-title">
                        <div class="header-title-main">
                            {{ $reportTitle }}
                        </div>

                        <div class="header-title-sub">
                            {{ $reportSubtitle }}
                        </div>
                    </td>

                    <td class="header-right">
                        <table class="header-right-table">
                            <tr>
                                <td class="form-code-cell">
                                    <div class="form-code-box">
                                        FORM K3-02.2
                                    </div>
                                </td>

                                <td class="k3-logo-cell">
                                    @if($k3LogoData)
                                        <img
                                            src="{{ $k3LogoData }}"
                                            alt="Logo K3"
                                            class="k3-logo-image"
                                        >
                                    @else
                                        <span class="k3-logo-fallback">
                                            K3
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- INFORMASI FORM --}}
            <table class="header-information">
                <tr>
                    <td class="info-left">
                        <table class="info-table">
                            <tr>
                                <td class="info-label">
                                    NOMOR
                                </td>

                                <td class="info-colon">
                                    :
                                </td>

                                <td class="info-value">
                                    {{ $reportNumber }}
                                </td>
                            </tr>

                            <tr>
                                <td class="info-label">
                                    PROYEK
                                </td>

                                <td class="info-colon">
                                    :
                                </td>

                                <td class="info-value">
                                    {{ $projectName }}
                                </td>
                            </tr>

                            <tr>
                                <td class="info-label">
                                    TLPG REGIONAL
                                </td>

                                <td class="info-colon">
                                    :
                                </td>

                                <td class="info-value">
                                    {{ $group['tlpg'] }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="info-right">
                        <table class="info-table">
                            <tr>
                                <td class="info-label-small">
                                    HARI / TGL
                                </td>

                                <td class="info-colon">
                                    :
                                </td>

                                <td class="info-value">
                                    {{ $headerDateText }}
                                </td>
                            </tr>

                            <tr>
                                <td class="info-label-small">
                                    JAM
                                </td>

                                <td class="info-colon">
                                    :
                                </td>

                                <td class="info-value">
                                    {{ $headerHours }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="info-page">
                        <span class="page-number-label">
                            HAL :
                        </span>

                        <span class="page-number-value">
                            {{ $currentPage }} /
                            {{ $totalPages }}
                        </span>
                    </td>
                </tr>
            </table>

            {{-- PERSETUJUAN --}}
            <table class="approval-table">
                <tr>
                    <th
                        colspan="8"
                        class="topic-title"
                    >
                        TOPIK / ITEM YANG DIPERIKSA
                    </th>
                </tr>

                <tr>
                    <th
                        colspan="4"
                        class="approval-group-title"
                    >
                        Dilaporkan Oleh :
                    </th>

                    <th class="approval-group-title">
                        Mengetahui Oleh :
                    </th>

                    <th
                        colspan="2"
                        class="approval-group-title"
                    >
                        Diterima Oleh :
                    </th>

                    <th class="approval-group-title">
                        Disetujui Oleh :
                    </th>
                </tr>

                <tr>
                    @foreach($signaturePersons as $person)
                        <td class="approval-role">
                            {{ $person['role'] }}
                        </td>
                    @endforeach
                </tr>

                <tr>
                    @foreach($signaturePersons as $person)
                        <td class="approval-signature">
                            <div class="signature-box">
                                @if($person['data_uri'])
                                    <img
                                        src="{{ $person['data_uri'] }}"
                                        alt="Tanda tangan {{ $person['name'] }}"
                                    >
                                @else
                                    <span class="signature-empty">
                                        &nbsp;
                                    </span>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>

                <tr>
                    @foreach($signaturePersons as $person)
                        <td class="approval-name">
                            {{ $person['name'] }}
                        </td>
                    @endforeach
                </tr>

                <tr>
                    <td
                        colspan="8"
                        class="approval-note"
                    >
                        (FAKTOR KONSTRUKSI) MASUKKAN JUMLAH
                        NOTIFIKASI BERDASARKAN TOPIK / ITEM
                        DIPERIKSA YANG MENYIMPANG DARI
                        KETENTUAN K3 SELAMA
                        {{ $mode === 'daily'
                            ? '1 MINGGU'
                            : '1 BULAN' }}
                    </td>
                </tr>
            </table>

            {{-- TABEL LAPORAN --}}
            <table class="data-table {{ $densityClass }}">
                <colgroup>
                    {{-- NO --}}
                    <col style="width:6mm;">

                    {{-- MT / NOPOL --}}
                    <col style="width:22mm;">

                    {{-- TLPG / TERMINALS --}}
                    <col style="width:26mm;">

                    {{-- AMT / DRIVER --}}
                    <col style="width:29mm;">

                    {{-- 22 JENIS PELANGGARAN --}}
                    @foreach($masterCategories as $category)
                        <col style="width:9mm;">
                    @endforeach
                </colgroup>

                <thead>
                    <tr>
                        <th
                            rowspan="2"
                            class="main-header-cell fixed-header header-no"
                        >
                            NO
                        </th>

                        <th
                            rowspan="2"
                            class="main-header-cell fixed-header"
                        >
                            MT /<br>NOPOL
                        </th>

                        <th
                            rowspan="2"
                            class="main-header-cell fixed-header"
                        >
                            TLPG /<br>TERMINAL
                        </th>

                        <th
                            rowspan="2"
                            class="main-header-cell fixed-header"
                        >
                            AMT /<br>DRIVER
                        </th>

                        <th
                            colspan="{{ count($sp1Categories) }}"
                            class="group-header"
                        >
                            SP 1
                        </th>

                        <th
                            colspan="{{ count($sp2Categories) }}"
                            class="group-header"
                        >
                            SP 2
                        </th>

                        <th
                            colspan="{{ count($sp3Categories) }}"
                            class="group-header"
                        >
                            SP 3
                        </th>

                        <th
                            colspan="{{ count($returnCategories) }}"
                            class="group-header"
                        >
                            PENGEMBALIAN
                        </th>
                    </tr>

                    <tr>
                        @foreach($masterCategories as $category)
                            <th class="category-header">
                                <div class="category-rotate">
                                    <span class="category-text">
                                        {{ $category }}
                                    </span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($pageRows as $rowIndex => $row)
                        <tr>
                            <td class="cell-number">
                                {{ $rowIndex + 1 }}
                            </td>

                            <td class="cell-nopol">
                                {{ $row['nopol'] }}
                            </td>

                            <td class="cell-tlpg">
                                {{ $row['tlpg'] }}
                            </td>

                            <td class="cell-driver">
                                {{ $row['driver'] ?? '-' }}
                            </td>

                            @foreach($masterCategories as $category)
                                @php
                                    $value = (int) (
                                        $row['counts'][$category]
                                        ?? 0
                                    );
                                @endphp

                                <td
                                    class="{{ $value > 0
                                        ? 'value-filled'
                                        : 'value-empty' }}"
                                >
                                    {{ $value > 0
                                        ? $value
                                        : '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="report-footer">
                <tr>
                    <td>
                        Sumber:
                        {{ $sourceLabel }}
                    </td>

                    <td class="footer-right">
                        Dicetak dari SIMOLA:
                        {{ $generatedAt->format('d-m-Y H:i:s') }}
                    </td>
                </tr>
            </table>
        </section>
    @endforeach
</body>
</html>