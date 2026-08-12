<?php

namespace App\Http\Controllers;

use App\Models\MonitoringEvent;
use App\Models\ReportUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UploadTerpaduController extends Controller
{
    public function index()
    {
        return view('upload-terpadu.index');
    }

    public function store(Request $request)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        $request->validate([
            'jenis_laporan' => 'required|string',
            'periode' => 'required|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'bulan' => 'nullable',
            'tahun' => 'nullable',
            'file_laporan' => 'required',
            'file_laporan.*' => 'file|mimes:xlsx,xls,csv,pdf|max:102400',
        ]);

        $files = $request->file('file_laporan');

        if (!is_array($files)) {
            $files = [$files];
        }

        $jenisLaporan = $request->jenis_laporan;
        $totalSemua = 0;
        $totalFileBerhasil = 0;
        $fileDuplikat = 0;
        $fileGagal = [];
        $fileDuplikatList = [];

        foreach ($files as $file) {
            DB::beginTransaction();

            try {
                $originalName = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $fileHash = hash_file('sha256', $file->getRealPath());

                $sudahAda = ReportUpload::where('file_hash', $fileHash)->first();

                if ($sudahAda) {
                    DB::rollBack();

                    $fileDuplikat++;
                    $fileDuplikatList[] = $originalName;

                    continue;
                }

                $path = $file->store('upload-terpadu');

                $reportUpload = ReportUpload::create([
                    'jenis_laporan' => $jenisLaporan,
                    'periode' => $request->periode,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                    'bulan' => $request->bulan,
                    'tahun' => $request->tahun,
                    'nama_file' => $originalName,
                    'path_file' => $path,
                    'file_hash' => $fileHash,
                    'uploaded_by' => auth()->id(),
                    'status' => 'Diproses',
                    'total_data' => 0,
                ]);

                if ($jenisLaporan === 'pelanggaran' && $extension === 'pdf') {
                    $totalImport = $this->importPelanggaranPdfDariNamaFile($reportUpload, $originalName, $request);
                } elseif ($jenisLaporan === 'pelanggaran') {
                    $totalImport = $this->importPelanggaranExcel($reportUpload, Storage::path($path), $request);
                } elseif ($jenisLaporan === 'errorlog') {
                    $totalImport = $this->importErrorlogExcel($reportUpload, Storage::path($path), $request);
                } elseif ($jenisLaporan === 'kendala') {
                    $totalImport = $this->importKendalaPdf($reportUpload, Storage::path($path), $request);
                } elseif ($jenisLaporan === 'accident') {
                    $totalImport = $this->importAccidentPdf($reportUpload, Storage::path($path), $request);
                } else {
                    throw new \Exception('Importer untuk jenis laporan ini belum tersedia.');
                }

                $reportUpload->update([
                    'status' => 'Berhasil',
                    'total_data' => $totalImport,
                ]);

                DB::commit();

                $totalSemua += $totalImport;
                $totalFileBerhasil++;
            } catch (\Throwable $e) {
                DB::rollBack();

                if (isset($path) && $path && Storage::exists($path)) {
                    Storage::delete($path);
                }

                $fileGagal[] = [
                    'nama_file' => $file->getClientOriginalName(),
                    'pesan' => mb_substr($e->getMessage(), 0, 350),
                ];

                continue;
            }
        }

        $ringkasan = [
            'total_dipilih' => count($files),
            'berhasil' => $totalFileBerhasil,
            'data_masuk' => $totalSemua,
            'duplikat' => $fileDuplikat,
            'file_duplikat' => $fileDuplikatList,
            'gagal' => count($fileGagal),
            'file_gagal' => $fileGagal,
        ];

        return redirect()
            ->route('upload-terpadu.index')
            ->with('upload_summary', $ringkasan);
        }

    private function importErrorlogExcel(
            ReportUpload $reportUpload,
            string $filePath,
            Request $request
        ): int {
            $spreadsheet = IOFactory::load($filePath);

            $sheet = null;

            /*
            * Cari sheet berdasarkan nama yang sudah dinormalisasi.
            *
            * "Error Log System" menjadi "errorlogsystem".
            */
            foreach ($spreadsheet->getWorksheetIterator() as $candidate) {
                $normalizedName = strtolower(
                    preg_replace(
                        '/[^a-z0-9]/i',
                        '',
                        $candidate->getTitle()
                    )
                );

                if (
                    $normalizedName === 'errorlogsystem' ||
                    $normalizedName === 'errorlog' ||
                    str_contains($normalizedName, 'errorlog')
                ) {
                    $sheet = $candidate;
                    break;
                }
            }

            if (!$sheet) {
                throw new \RuntimeException(
                    'Sheet Error Log System tidak ditemukan. ' .
                    'Sheet yang tersedia: ' .
                    implode(', ', $spreadsheet->getSheetNames())
                );
            }

            $highestRow = $sheet->getHighestDataRow();
            $totalMasuk = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                /*
                * Struktur sheet Error Log System:
                *
                * B = Shift
                * C = Pelapor
                * D = No PC
                * E = Nopol
                * F = TLPG
                * G = Remarks
                * H = Waktu
                * I = Jenis Error
                * J = Evidence
                * K = No E-Ticket
                * L = Update RTC
                * M = Status Mceasy
                */
                $shift = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'B' . $row
                ));

                $pelapor = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'C' . $row
                ));

                $noPc = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'D' . $row
                ));

                $nopol = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'E' . $row
                ));

                $tlpg = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'F' . $row
                ));

                $remarks = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'G' . $row
                ));

                $waktuRaw = $this->readCachedExcelCell(
                    $sheet,
                    'H' . $row
                );

                $jenisError = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'I' . $row
                ));

                $evidence = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'J' . $row
                ));

                $ticketNumber = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'K' . $row
                ));

                $updateRtc = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'L' . $row
                ));

                $statusMceasy = trim((string) $this->readCachedExcelCell(
                    $sheet,
                    'M' . $row
                ));

                /*
                * Abaikan baris kosong atau baris formula kosong.
                */
                if (
                    $ticketNumber === '' &&
                    $nopol === '' &&
                    $jenisError === '' &&
                    ($waktuRaw === null || $waktuRaw === '')
                ) {
                    continue;
                }

                $waktu = $this->parseErrorlogExcelDateTime(
                    $waktuRaw
                );

                /*
                * Data utama wajib tersedia.
                */
                if (
                    !$waktu ||
                    $nopol === '' ||
                    $jenisError === ''
                ) {
                    continue;
                }

                /*
                * Abaikan hasil formula yang gagal dihitung.
                */
                if (
                    str_starts_with($jenisError, '#') ||
                    strtoupper($jenisError) === 'COMPUTED_VALUE'
                ) {
                    continue;
                }

                $nopol = strtoupper(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $nopol
                    )
                );

                $tlpg = strtoupper(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $tlpg
                    )
                );

                MonitoringEvent::create([
                    'report_upload_id' => $reportUpload->id,

                    'event_type' => 'errorlog',

                    'event_date' => $waktu->toDateString(),

                    'event_time' => $waktu->format('H:i:s'),

                    'nopol' => $nopol,

                    'driver_name' => $pelapor ?: null,

                    'tlpg' => $tlpg ?: null,

                    'event_name' => $jenisError,

                    'category' => $remarks ?: 'Error System',

                    'severity' => 'sedang',

                    'score_impact' => 0,

                    'source_page' => null,

                    'source_row' => $row,

                    'evidence_link' => $evidence ?: null,

                    'description' => $remarks ?: $jenisError,

                    'ticket_number' => $ticketNumber ?: null,

                    /*
                    * Status utama dari Mceasy.
                    * Jika kosong, gunakan status tindak lanjut RTC.
                    */
                    'event_status' => $statusMceasy ?: $updateRtc ?: null,

                    'follow_up_status' => $updateRtc ?: null,

                    'raw_data' => json_encode([
                        'shift' => $shift,
                        'pelapor' => $pelapor,
                        'no_pc' => $noPc,
                        'nopol' => $nopol,
                        'tlpg' => $tlpg,
                        'remarks' => $remarks,
                        'waktu' => $waktu->format('Y-m-d H:i:s'),
                        'jenis_error' => $jenisError,
                        'evidence' => $evidence,
                        'no_e_ticket' => $ticketNumber,
                        'update_rtc' => $updateRtc,
                        'status_mceasy' => $statusMceasy,
                        'source_row' => $row,
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $totalMasuk++;
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($totalMasuk === 0) {
                throw new \RuntimeException(
                    'Sheet Error Log System ditemukan, tetapi tidak ada data yang berhasil diimport. ' .
                    'Pastikan kolom E sampai M masih sesuai dengan format Errorlog.'
                );
            }

            return $totalMasuk;
        }

    private function importKendalaPdf(ReportUpload $reportUpload, string $filePath, Request $request): int
    {
        $infoDariNamaFile = $this->parseNamaFileKendala($reportUpload->nama_file, $request);

        if ($infoDariNamaFile) {
            MonitoringEvent::create([
                'report_upload_id' => $reportUpload->id,
                'event_type' => 'kendala',
                'event_date' => $infoDariNamaFile['tanggal'],
                'event_time' => null,
                'nopol' => $infoDariNamaFile['nopol'],
                'driver_name' => null,
                'tlpg' => $infoDariNamaFile['tlpg'],
                'event_name' => $infoDariNamaFile['event_name'],
                'category' => 'Kendala',
                'severity' => 'sedang',
                'score_impact' => 10,
                'source_page' => null,
                'source_row' => null,
                'evidence_link' => null,
                'description' => 'Data kendala dari nama file: ' . $reportUpload->nama_file,
                'raw_data' => [
                    'nama_file' => $reportUpload->nama_file,
                    'sumber' => 'filename',
                ],
            ]);

            return 1;
        }

        $text = $this->extractPdfTextWithPdftotext($filePath);
        $text = $this->rapikanTextPdf($text);

        if (trim($text) === '') {
            throw new \Exception('Teks PDF kosong. Kemungkinan PDF berupa gambar/scanned dan perlu OCR.');
        }

        $blocks = preg_split('/(?=FORM REPORT\s+KENDALA MOBIL TANGKI)/i', $text);

        $totalImport = 0;
        $sampleText = mb_substr($text, 0, 1000);

        foreach ($blocks as $index => $block) {
            if (!str_contains(strtoupper($block), 'KENDALA MOBIL TANGKI')) {
                continue;
            }

            $tanggal = $this->ambilRegex('/Tanggal\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $block);
            $jamLaporan = $this->ambilRegex('/Jam\s*:\s*([0-9]{1,2}:[0-9]{2})/i', $block);

            $namaAmt = $this->ambilRegex('/Nama\s+AMT\s*:\s*(.*?)\s+Nopol\s*\/\s*Alat\s+Kerja/i', $block);
            $nopol = $this->ambilRegex('/Nopol\s*\/\s*Alat\s+Kerja\s*:\s*([A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3})/i', $block);
            $tlpg = $this->ambilRegex('/TLPG\s*:\s*(.*?)\s+Status\s+Muatan/i', $block);

            $criticalEvent = $this->ambilRegex('/Critical\s+Event\s*:\s*(.*?)\s+b\.\s*Kemungkinan\s+Penyebab/i', $block);

            if (!$criticalEvent) {
                $criticalEvent = $this->ambilRegex('/Lain-lain\s*:\s*(.*?)\s*(?:\|\||Akibat|Waktu Kejadian)/i', $block);
            }

            $kemungkinanPenyebab = $this->ambilRegex('/Kemungkinan\s+Penyebab\s*:\s*(.*?)\s+c\.\s*Faktor\s+Pendukung/i', $block);
            $faktorPendukung = $this->ambilRegex('/Faktor\s+Pendukung\s*:\s*(.*?)\s+2\.2\.\s*DAMPAK/i', $block);
            $dampak = $this->ambilRegex('/2\.2\.\s*DAMPAK\s*&\s*KONDISI\s*(.*?)\s+HASIL\s+PEMERIKSAAN/i', $block);
            $lokasi = $this->ambilRegex('/Lokasi\s+Kejadian\s*:\s*(.*?)\s+Mengetahui/i', $block);
            $evidence = $this->ambilRegex('/EVIDENCE\s*(https?:\/\/\S+)/i', $block);

            $eventDate = $this->formatTanggalPdf($tanggal) ?? $this->tanggalUtama($request);

            $nopol = $this->normalisasiNopol($nopol);
            $tlpg = $this->normalisasiText($tlpg);
            $namaAmt = $this->normalisasiText($namaAmt);
            $criticalEvent = $this->normalisasiText($criticalEvent);

            if (!$nopol && !$tlpg && !$criticalEvent) {
                continue;
            }

            MonitoringEvent::create([
                'report_upload_id' => $reportUpload->id,
                'event_type' => 'kendala',
                'event_date' => $eventDate,
                'event_time' => $jamLaporan,
                'nopol' => $nopol,
                'driver_name' => $namaAmt,
                'tlpg' => $tlpg,
                'event_name' => $criticalEvent,
                'category' => 'Near Miss',
                'severity' => $this->severityKendala($criticalEvent),
                'score_impact' => $this->scoreKendala($criticalEvent),
                'source_page' => $index + 1,
                'source_row' => null,
                'evidence_link' => $evidence,
                'description' => $dampak,
                'raw_data' => [
                    'tanggal_pdf' => $tanggal,
                    'jam_laporan' => $jamLaporan,
                    'kemungkinan_penyebab' => $kemungkinanPenyebab,
                    'faktor_pendukung' => $faktorPendukung,
                    'lokasi' => $lokasi,
                    'nama_file' => $reportUpload->nama_file,
                ],
            ]);

            $totalImport++;
        }

        if ($totalImport === 0) {
            throw new \Exception(
                'PDF berhasil dibaca dengan pdftotext, tetapi pola data Kendala belum cocok. Cuplikan teks: ' . $sampleText
            );
        }

        return $totalImport;
    }

    private function importAccidentPdf(ReportUpload $reportUpload, string $filePath, Request $request): int
{
    $text = $this->extractPdfTextWithPdftotext($filePath);
    $text = $this->rapikanTextPdf($text);

    if (!$text || strlen(trim($text)) < 30 || str_starts_with(trim($text), '%PDF')) {
        throw new \Exception('PDF Accident tidak bisa dibaca sebagai teks. Kemungkinan file berbentuk scan/gambar.');
    }

    $info = $this->parseAccidentPdfBaru($text, $reportUpload->nama_file, $request);

    if (!$info) {
        throw new \Exception(
            'PDF Accident berhasil dibaca, tetapi pola data belum cocok. Pastikan PDF memuat tanggal, NOPOL, terminal/TLPG, dan jenis accident AKTIF/PASIF.'
        );
    }

    MonitoringEvent::create([
        'report_upload_id' => $reportUpload->id,
        'event_type' => 'accident',
        'event_date' => $info['tanggal'],
        'event_time' => $info['jam'],
        'nopol' => $info['nopol'],
        'driver_name' => $info['driver_name'],
        'tlpg' => $info['tlpg'],
        'event_name' => 'Accident ' . $info['type_accident'],
        'category' => $info['type_accident'],
        'severity' => $info['type_accident'] === 'AKTIF' ? 'kritis' : 'tinggi',
        'score_impact' => $info['type_accident'] === 'AKTIF' ? 30 : 20,
        'source_page' => null,
        'source_row' => null,
        'evidence_link' => $info['evidence'],
        'description' => 'Data accident dari file PDF: ' . $reportUpload->nama_file,
        'raw_data' => [
            'nama_file' => $reportUpload->nama_file,
            'sumber' => 'pdf_text',
            'type_accident' => $info['type_accident'],
        ],
    ]);

    return 1;
}

    private function tanggalUtama(Request $request): ?string
    {
        if ($request->filled('tanggal_mulai')) {
            return $request->tanggal_mulai;
        }

        if ($request->filled('tahun') && $request->filled('bulan')) {
            $bulan = str_pad($request->bulan, 2, '0', STR_PAD_LEFT);
            $tanggalAwal = $request->tahun . '-' . $bulan . '-01';

            return date('Y-m-t', strtotime($tanggalAwal));
        }

        return null;
    }

    private function kolomPelanggaran(): array
    {
        return [
            'H' => ['kategori' => 'SP 1', 'jenis' => 'Menerima Penumpang Selain AMT'],
            'I' => ['kategori' => 'SP 1', 'jenis' => 'Mengemudi Lebih dari 4 Jam'],
            'J' => ['kategori' => 'SP 1', 'jenis' => 'Over Speed'],
            'K' => ['kategori' => 'SP 1', 'jenis' => 'Perlambatan Mendadak'],
            'L' => ['kategori' => 'SP 1', 'jenis' => 'Akselerasi Mendadak'],
            'M' => ['kategori' => 'SP 1', 'jenis' => 'Tikungan Tajam'],
            'N' => ['kategori' => 'SP 1', 'jenis' => 'Melebihi Batas Waktu Parkir'],
            'O' => ['kategori' => 'SP 1', 'jenis' => 'Seat Belt'],

            'P' => ['kategori' => 'SP 2', 'jenis' => 'Keluar Rute'],

            'Q' => ['kategori' => 'SP 3', 'jenis' => 'Berganti AMT Tanpa Lisensi'],
            'R' => ['kategori' => 'SP 3', 'jenis' => 'Menggunakan Handphone / Gadget'],

            'S' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Merokok / Vape'],
            'T' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Menutup / Mengubah Posisi CAM'],
            'U' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Merusak / Melepas Device GPS / CAM'],
            'V' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Pengurangan Bahan Bakar'],
            'W' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Berganti AMT Tidak Berlisensi Accident'],
            'X' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Pengemudi Kelelahan Accident'],
            'Y' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Mengemudi Tidak Baik Napza / Alkohol'],
            'Z' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Menghilangkan Sinyal GPS Jammer'],
            'AA' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Geolokasi Blackzone & Redzone'],
            'AB' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Pelecehan Verbal'],
            'AC' => ['kategori' => 'PENGEMBALIAN', 'jenis' => 'Mengintervensi / Mengancam / Bekerja Sama Dengan Petugas RTC'],
        ];
    }

    private function severityPelanggaran(?string $kategori): string
    {
        return match ($kategori) {
            'SP 1' => 'rendah',
            'SP 2' => 'sedang',
            'SP 3' => 'tinggi',
            'PENGEMBALIAN' => 'kritis',
            default => 'rendah',
        };
    }

    private function scorePelanggaran(?string $kategori): int
    {
        return match ($kategori) {
            'SP 1' => 5,
            'SP 2' => 10,
            'SP 3' => 20,
            'PENGEMBALIAN' => 30,
            default => 0,
        };
    }

    private function formatTanggalExcel($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return date('Y-m-d', strtotime((string) $value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatWaktuExcel($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i:s');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('H:i:s');
            }

            return date('H:i:s', strtotime((string) $value));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalisasiNopol(?string $nopol): ?string
    {
        if (!$nopol) {
            return null;
        }

        return strtoupper(trim($nopol));
    }

    private function severityErrorlog(?string $jenisError): string
    {
        $jenisError = strtolower((string) $jenisError);

        if (str_contains($jenisError, 'offline')) {
            return 'sedang';
        }

        if (str_contains($jenisError, 'failed')) {
            return 'sedang';
        }

        if (str_contains($jenisError, 'gps')) {
            return 'sedang';
        }

        return 'rendah';
    }

    private function scoreErrorlog(?string $jenisError): int
    {
        $jenisError = strtolower((string) $jenisError);

        if (str_contains($jenisError, 'trackvision offline')) {
            return 2;
        }

        if (str_contains($jenisError, 'gps offline')) {
            return 2;
        }

        if (str_contains($jenisError, 'streaming failed')) {
            return 2;
        }

        return 1;
    }

    private function rapikanTextPdf(string $text): string
    {
        $text = str_replace(["\r", "\t"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function ambilRegex(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function formatTanggalPdf(?string $tanggal): ?string
    {
        if (!$tanggal) {
            return null;
        }

        try {
            $parts = explode('/', $tanggal);

            if (count($parts) === 3) {
                return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }

            return date('Y-m-d', strtotime($tanggal));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalisasiText(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);

        if ($text === '-' || $text === '') {
            return null;
        }

        return $text;
    }

    private function severityKendala(?string $criticalEvent): string
    {
        $criticalEvent = strtolower((string) $criticalEvent);

        if (str_contains($criticalEvent, 'rem') || str_contains($criticalEvent, 'mesin')) {
            return 'tinggi';
        }

        if (str_contains($criticalEvent, 'ban') || str_contains($criticalEvent, 'oli')) {
            return 'sedang';
        }

        return 'rendah';
    }

    private function scoreKendala(?string $criticalEvent): int
    {
        $criticalEvent = strtolower((string) $criticalEvent);

        if (str_contains($criticalEvent, 'rem')) {
            return 15;
        }

        if (str_contains($criticalEvent, 'mesin')) {
            return 12;
        }

        if (str_contains($criticalEvent, 'ban')) {
            return 8;
        }

        if (str_contains($criticalEvent, 'oli')) {
            return 6;
        }

        return 5;
    }

    private function extractPdfTextWithPdftotext(string $filePath): string
    {
        $outputPath = storage_path('app/temp_pdf_' . uniqid() . '.txt');

        $binary = env('PDFTOTEXT_PATH', 'pdftotext');

        $command = '"' . $binary . '" -layout -enc UTF-8 "' . $filePath . '" "' . $outputPath . '" 2>&1';

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputPath)) {
            throw new \Exception(
                'Gagal menjalankan pdftotext. Pastikan Poppler/pdftotext sudah terinstall. Output: ' . implode(' ', $output)
            );
        }

        $text = file_get_contents($outputPath);

        @unlink($outputPath);

        return $text ?: '';
    }

    private function ambilNilai($sheet, string $cell)
    {
        $objCell = $sheet->getCell($cell);

        try {
            $value = $objCell->getCalculatedValue();

            if ($value !== null && $value !== '' && $value !== '#NAME?') {
                return $value;
            }
        } catch (\Throwable $e) {
            //
        }

        try {
            $oldValue = $objCell->getOldCalculatedValue();

            if ($oldValue !== null && $oldValue !== '' && $oldValue !== '#NAME?') {
                return $oldValue;
            }
        } catch (\Throwable $e) {
            //
        }

        $raw = $objCell->getValue();

        if (is_string($raw) && str_starts_with($raw, '=')) {
            $fallback = $this->ambilFallbackDariFormula($raw);

            if ($fallback !== null) {
                return $fallback;
            }
        }

        return $raw;
    }

    private function ambilFallbackDariFormula(string $formula): ?string
    {
        if (preg_match('/,\s*"([^"]*)"\s*\)$/', $formula, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function bersihkanTeks($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '#NAME?') {
            return null;
        }

        return $value;
    }

    private function adaPelanggaran($value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '0' || $value === '-' || strtoupper($value) === 'FALSE') {
            return false;
        }

        return true;
    }

    private function severityAccident(?string $typeAccident): string
    {
        $typeAccident = strtolower((string) $typeAccident);

        if (str_contains($typeAccident, 'aktif')) {
            return 'kritis';
        }

        if (str_contains($typeAccident, 'pasif')) {
            return 'tinggi';
        }

        return 'sedang';
    }

    private function scoreAccident(?string $typeAccident): int
    {
        $typeAccident = strtolower((string) $typeAccident);

        if (str_contains($typeAccident, 'aktif')) {
            return 35;
        }

        if (str_contains($typeAccident, 'pasif')) {
            return 15;
        }

        return 10;
    }

    private function normalisasiNomorTiket($value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        return (string) intval($value);
    }

    $value = trim((string) $value);

    if ($value === '' || $value === '-') {
        return null;
    }

    return $value;
}

public function files(Request $request)
{
    $query = ReportUpload::withCount('events')
        ->latest();

    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->where(function ($q) use ($search) {
            $q->where('nama_file', 'like', '%' . $search . '%')
              ->orWhere('jenis_laporan', 'like', '%' . $search . '%')
              ->orWhere('periode', 'like', '%' . $search . '%')
              ->orWhere('status', 'like', '%' . $search . '%');
        });
    }

    if ($request->filled('jenis_laporan')) {
        $query->where('jenis_laporan', $request->jenis_laporan);
    }

    if ($request->filled('tanggal')) {
        try {
            $date = \Carbon\Carbon::parse($request->tanggal);

            $bulanIndonesia = [
                1 => 'JANUARI',
                2 => 'FEBRUARI',
                3 => 'MARET',
                4 => 'APRIL',
                5 => 'MEI',
                6 => 'JUNI',
                7 => 'JULI',
                8 => 'AGUSTUS',
                9 => 'SEPTEMBER',
                10 => 'OKTOBER',
                11 => 'NOVEMBER',
                12 => 'DESEMBER',
            ];

            $tanggal = $date->format('Y-m-d');
            $hari = (int) $date->format('d');
            $bulanNama = $bulanIndonesia[(int) $date->format('n')];
            $tahun = $date->format('Y');

            /*
             * REGEXP ini memastikan tanggal cocok tepat.
             * Contoh filter 2 JUNI 2026:
             * Cocok    : 2 JUNI 2026, 02 JUNI 2026
             * Tidak cocok: 12 JUNI 2026, 22 JUNI 2026
             */
            $regexNamaFile = '(^|[^0-9])0?' . $hari . '[[:space:]]+' . $bulanNama . '[[:space:]]+' . $tahun . '([^0-9]|$)';

            $query->where(function ($q) use ($tanggal, $regexNamaFile) {
                $q->whereDate('created_at', $tanggal)
                  ->orWhereDate('tanggal_mulai', $tanggal)
                  ->orWhereDate('tanggal_selesai', $tanggal)
                  ->orWhereRaw('UPPER(nama_file) REGEXP ?', [$regexNamaFile])
                  ->orWhereHas('events', function ($eventQuery) use ($tanggal) {
                      $eventQuery->whereDate('event_date', $tanggal);
                  });
            });
        } catch (\Throwable $e) {
            // Kalau format tanggal tidak valid, abaikan filter tanggal.
        }
    }

    $files = $query->paginate(15)->appends($request->query());

    $jenisOptions = ReportUpload::query()
        ->select('jenis_laporan')
        ->whereNotNull('jenis_laporan')
        ->distinct()
        ->orderBy('jenis_laporan')
        ->pluck('jenis_laporan');

    return view('upload-terpadu.files', compact('files', 'jenisOptions'));
}

public function destroy(ReportUpload $reportUpload)
{
    if ($reportUpload->path_file && Storage::exists($reportUpload->path_file)) {
        Storage::delete($reportUpload->path_file);
    }

    $reportUpload->delete();

    return redirect()
        ->route('upload-terpadu.files')
        ->with('success', 'File upload dan seluruh data monitoring dari file tersebut berhasil dihapus.');
}

    private function importPelanggaranPdfDariNamaFile(ReportUpload $reportUpload, string $originalName, Request $request): int
    {
        $info = $this->parseNamaFilePelanggaran($originalName, $request);

        if (!$info) {
            $info = $this->parseIsiPdfPelanggaran(Storage::path($reportUpload->path_file), $originalName, $request);
        }

        if (!$info) {
            throw new \Exception('Nama file dan isi PDF tidak bisa dibaca.');
        }

        MonitoringEvent::create([
            'report_upload_id' => $reportUpload->id,
            'event_type' => 'pelanggaran',
            'event_date' => $info['tanggal'],
            'event_time' => $info['jam'] ?? null,
            'nopol' => $info['nopol'],
            'driver_name' => $info['driver_name'] ?? null,
            'tlpg' => $info['tlpg'],
            'event_name' => $info['jenis_pelanggaran'],
            'category' => $info['kategori'],
            'severity' => $info['severity'],
            'score_impact' => $info['score'],
            'source_page' => null,
            'source_row' => null,
            'evidence_link' => $info['evidence'] ?? null,
            'description' => 'Data pelanggaran harian dari file PDF: ' . $originalName,
            'raw_data' => [
                'nama_file' => $originalName,
                'tanggal_dari_parser' => $info['tanggal'],
                'event_raw' => $info['event_raw'] ?? null,
            ],
        ]);

        return 1;
    }

    private function parseIsiPdfPelanggaran(string $filePath, string $originalName, Request $request): ?array
{
    $text = $this->extractPdfTextWithPdftotext($filePath);
    $text = $this->rapikanTextPdf($text);

    $tanggalText = $this->ambilRegex('/Tanggal\s*:\s*(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/i', $text);
    $jam = $this->ambilRegex('/Jam\s*:\s*([0-9]{1,2}:[0-9]{2}:[0-9]{2})/i', $text);

    if (!$tanggalText) {
        return null;
    }

    preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/i', $tanggalText, $tglMatch);

    if (!$tglMatch) {
        return null;
    }

    $hari = str_pad($tglMatch[1], 2, '0', STR_PAD_LEFT);
    $bulan = $this->bulanIndonesiaKeAngka($tglMatch[2]);
    $tahun = $tglMatch[3];

    if (!$bulan) {
        return null;
    }

    $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . $hari;

    $driver = $this->ambilRegex('/Jam\s*:\s*[0-9:]+\s+(.+?)\s+([A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3})/i', $text);
    $nopol = $this->ambilRegex('/\b([A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3})\b/i', $text);

    $jenis = $this->ambilRegex('/1\.\s*JENIS PELANGGARAN\s*:\s*(.+?)\s*2\.\s*URAIAN KRONOLOGI/i', $text);

    if (!$jenis) {
        $jenis = $this->ambilRegex('/pelanggaran yaitu\s+(.+?)\s+pada pukul/i', $text);
    }

    $evidence = $this->ambilRegex('/Evidence\s*:\s*(https?:\/\/\S+)/i', $text);

    [$tlpg, $eventRawFromName] = $this->ambilTlpgDanEventDariNamaFile(strtoupper(str_replace(['_', '-'], ' ', pathinfo($originalName, PATHINFO_FILENAME))));

    $jenisPelanggaran = $this->normalisasiJenisPelanggaranNamaFile($jenis ?: $eventRawFromName);
    $kategori = $this->kategoriPelanggaran($jenisPelanggaran);

    return [
        'tanggal' => $tanggal,
        'jam' => $jam,
        'nopol' => $this->normalisasiNopol($nopol),
        'driver_name' => $this->normalisasiText($driver),
        'tlpg' => $tlpg ?? '-',
        'event_raw' => $jenis,
        'jenis_pelanggaran' => $jenisPelanggaran,
        'kategori' => $kategori,
        'severity' => $this->severityPelanggaran($kategori),
        'score' => $this->scorePelanggaran($kategori),
        'evidence' => $evidence,
    ];
}


private function parseNamaFilePelanggaran(string $originalName, Request $request): ?array
{
    $filename = pathinfo($originalName, PATHINFO_FILENAME);
    $text = strtoupper($filename);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    if (!preg_match('/(\d{1,2})\s+(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER)/i', $text, $dateMatch)) {
        return null;
    }

    $hari = str_pad($dateMatch[1], 2, '0', STR_PAD_LEFT);
    $bulanAngka = $this->bulanIndonesiaKeAngka($dateMatch[2]);

    if (!$bulanAngka) {
        return null;
    }

    $tahun = $request->tahun ?: date('Y');
    $tanggal = $tahun . '-' . str_pad($bulanAngka, 2, '0', STR_PAD_LEFT) . '-' . $hari;

    if (!preg_match('/\b([A-Z]{1,2})\s*(\d{3,5})\s*([A-Z]{1,3})\b/', $text, $nopolMatch)) {
        return null;
    }

    $nopol = trim($nopolMatch[1] . ' ' . $nopolMatch[2] . ' ' . $nopolMatch[3]);

    $sisaSetelahNopol = $text;

if (preg_match('/\b[A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3}\b\s*(.+)$/i', $text, $restMatch)) {
    $sisaSetelahNopol = trim($restMatch[1]);
}

[$tlpg, $eventRaw] = $this->ambilTlpgDanEventDariNamaFile($sisaSetelahNopol);

if (!$tlpg) {
    return null;
}

if (!$eventRaw || trim($eventRaw) === '') {
    return null;
}

if (!$eventRaw || trim($eventRaw) === '') {
    return null;
}

    $jenisPelanggaran = $this->normalisasiJenisPelanggaranNamaFile($eventRaw);
    $kategori = $this->kategoriPelanggaran($jenisPelanggaran);
    $severity = $this->severityPelanggaran($kategori);
    $score = $this->scorePelanggaran($kategori);

    return [
        'tanggal' => $tanggal,
        'nopol' => $nopol,
        'tlpg' => $tlpg,
        'event_raw' => $eventRaw,
        'jenis_pelanggaran' => $jenisPelanggaran,
        'kategori' => $kategori,
        'severity' => $severity,
        'score' => $score,
    ];
}

private function bulanIndonesiaKeAngka(string $bulan): ?int
{
    $bulan = strtoupper(trim($bulan));

    $map = [
        'JANUARI' => 1,
        'FEBRUARI' => 2,
        'MARET' => 3,
        'APRIL' => 4,
        'MEI' => 5,
        'JUNI' => 6,
        'JULI' => 7,
        'AGUSTUS' => 8,
        'SEPTEMBER' => 9,
        'OKTOBER' => 10,
        'NOVEMBER' => 11,
        'DESEMBER' => 12,
    ];

    return $map[$bulan] ?? null;
}

private function ambilTlpgDanEventDariNamaFile(string $text): array
{
    $text = strtoupper($text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    $terminals = [
        'TLPG TJ PERAK' => 'TLPG TJ PERAK',
        'TERMINAL TJ PERAK' => 'TLPG TJ PERAK',
        'TLPG PERAK' => 'TLPG TJ PERAK',
        'TJ PERAK' => 'TLPG TJ PERAK',

        'TLPG TJ WANGI' => 'TLPG TJ WANGI',
        'TERMINAL TJ WANGI' => 'TLPG TJ WANGI',
        'TJ WANGI' => 'TLPG TJ WANGI',

        'TLPG MEM GRESIK' => 'TLPG MEM GRESIK',
        'TERMINAL MEM GRESIK' => 'TLPG MEM GRESIK',
        'TLPG MEM' => 'TLPG MEM GRESIK',
        'MEM GRESIK' => 'TLPG MEM GRESIK',

        'TLPG TJ LOMBOK' => 'TLPG LOMBOK',
        'TLPG TERMINAL LOMBOK' => 'TLPG LOMBOK',
        'TERMINAL LOMBOK' => 'TLPG LOMBOK',
        'TLPG LOMBOK' => 'TLPG LOMBOK',

        'TLPG MANGGIS' => 'TLPG MANGGIS',
        'TERMINAL MANGGIS' => 'TLPG MANGGIS',

        'TLPG BIMA' => 'TLPG BIMA',
        'TERMINAL BIMA' => 'TLPG BIMA',
    ];

    foreach ($terminals as $keyword => $canonical) {
        $pos = strpos($text, $keyword);

        if ($pos !== false) {
            $eventRaw = trim(substr($text, $pos + strlen($keyword)));
            $eventRaw = preg_replace('/\s+/', ' ', $eventRaw);

            return [$canonical, $eventRaw];
        }
    }

    return [null, null];
}

private function normalisasiJenisPelanggaranNamaFile(?string $eventRaw): string
{
    $eventRaw = strtoupper(trim((string) $eventRaw));
    $eventRaw = preg_replace('/\s+/', ' ', $eventRaw);

    if (str_contains($eventRaw, 'OVERSPEED') || str_contains($eventRaw, 'OVER SPEED')) {
        return 'Over Speed';
    }

    if (str_contains($eventRaw, 'MELEBIHI BATAS WAKTU PARKIR')) {
        return 'Melebihi Batas Waktu Parkir';
    }

    if (
        str_contains($eventRaw, 'MENGGUNAKAN HP') ||
        str_contains($eventRaw, 'MENGGUNAKAN HANDPHONE') ||
        str_contains($eventRaw, 'MENGGUNAKAN HENPHONE')
    ) {
        return 'Menggunakan Handphone / Gadget';
    }

    if (str_contains($eventRaw, 'PELECEHAN VERBAL')) {
        return 'Pelecehan Verbal';
    }

    if (str_contains($eventRaw, 'SEAT BELT')) {
        return 'Seat Belt';
    }

    if (str_contains($eventRaw, 'KELUAR RUTE')) {
        return 'Keluar Rute';
    }

    if (str_contains($eventRaw, 'MEROKOK')) {
        return 'Merokok';
    }

    if (str_contains($eventRaw, 'BERGANTI AMT') || 
        str_contains($eventRaw, 'BERGANTI TANPA LISENSI') || 
        str_contains($eventRaw, 'BERGANTI AMT YANG TIDAK BERLISENSI')) {
        return 'Berganti AMT Yang Tidak Berlisensi';
    }

    if (str_contains($eventRaw, 'MENGEMUDI LEBIH DARI 1 JAM')) {
        return 'Mengemudi Lebih Dari 1 Jam';
    }

    if (str_contains($eventRaw, 'MENUTUP CAM') || str_contains($eventRaw, 'MENGUBAH POSISI KAMERA')) {
        return 'Menutup CAM';
    }

    return ucwords(strtolower($eventRaw));
}

private function kategoriPelanggaran(string $jenis): string
{
    $jenisLower = strtolower($jenis);

    if (str_contains($jenisLower, 'menggunakan handphone')) {
        return 'SP 3';
    }

    if (str_contains($jenisLower, 'pelecehan verbal')) {
        return 'PENGEMBALIAN';
    }

    if (str_contains($jenisLower, 'keluar rute')) {
        return 'SP 2';
    }

    return 'SP 1';
}

private function importPelanggaranExcel(ReportUpload $reportUpload, string $filePath, Request $request): int
{
    $spreadsheet = IOFactory::load($filePath);

    $sheet = $spreadsheet->getSheetByName('K3-02.2');

    if (!$sheet) {
        throw new \Exception('Sheet K3-02.2 tidak ditemukan di file Excel.');
    }

    $kolomPelanggaran = $this->kolomPelanggaran();

    $startRow = 25;
    $endRow = 356;
    $totalImport = 0;

    for ($row = $startRow; $row <= $endRow; $row++) {
        $noUrut = $this->ambilNilai($sheet, 'B' . $row);
        $nopol = $this->bersihkanTeks($this->ambilNilai($sheet, 'C' . $row));
        $terminal = $this->bersihkanTeks($this->ambilNilai($sheet, 'F' . $row));
        $driver = $this->bersihkanTeks($this->ambilNilai($sheet, 'G' . $row));
        $evidence = $this->bersihkanTeks($this->ambilNilai($sheet, 'AE' . $row));

        if (!$noUrut && !$nopol && !$terminal && !$driver) {
            continue;
        }

        foreach ($kolomPelanggaran as $kolom => $info) {
            $nilai = $this->ambilNilai($sheet, $kolom . $row);

            if (!$this->adaPelanggaran($nilai)) {
                continue;
            }

            MonitoringEvent::create([
                'report_upload_id' => $reportUpload->id,
                'event_type' => 'pelanggaran',
                'event_date' => $this->tanggalUtama($request),
                'event_time' => null,
                'nopol' => $this->normalisasiNopol($nopol),
                'driver_name' => $driver,
                'tlpg' => $terminal,
                'event_name' => $info['jenis'],
                'category' => $info['kategori'],
                'severity' => $this->severityPelanggaran($info['kategori']),
                'score_impact' => $this->scorePelanggaran($info['kategori']),
                'source_page' => null,
                'source_row' => $row,
                'evidence_link' => $evidence,
                'description' => 'Import pelanggaran dari Excel: ' . $reportUpload->nama_file,
                'raw_data' => [
                    'no_urut' => $noUrut,
                    'nilai' => $nilai,
                    'row_excel' => $row,
                    'nama_file' => $reportUpload->nama_file,
                ],
            ]);

            $totalImport++;
        }
    }

    return $totalImport;
}

public function viewFile(ReportUpload $reportUpload)
{
    if (!$reportUpload->path_file || !Storage::exists($reportUpload->path_file)) {
        abort(404, 'File tidak ditemukan.');
    }

    $filePath = Storage::path($reportUpload->path_file);
    $extension = strtolower(pathinfo($reportUpload->nama_file, PATHINFO_EXTENSION));

    if ($extension === 'pdf') {
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $reportUpload->nama_file . '"',
        ]);
    }

    return response()->download($filePath, $reportUpload->nama_file);
}

public function viewerFile(ReportUpload $reportUpload)
{
    if (!$reportUpload->path_file || !Storage::exists($reportUpload->path_file)) {
        abort(404, 'File tidak ditemukan.');
    }

    $extension = strtolower(pathinfo($reportUpload->nama_file, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        return redirect()
            ->route('upload-terpadu.download', $reportUpload->id);
    }

    return view('upload-terpadu.viewer', compact('reportUpload'));
}

public function previewFile(ReportUpload $reportUpload)
{
    if (!$reportUpload->path_file || !Storage::exists($reportUpload->path_file)) {
        abort(404, 'File tidak ditemukan.');
    }

    $filePath = Storage::path($reportUpload->path_file);
    $extension = strtolower(pathinfo($reportUpload->nama_file, PATHINFO_EXTENSION));

    if ($extension !== 'pdf') {
        return response()->download($filePath, $reportUpload->nama_file);
    }

    $safeName = str_replace(['"', "\n", "\r"], '', $reportUpload->nama_file);

    return response()->file($filePath, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $safeName . '"',
        'Content-Transfer-Encoding' => 'binary',
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, must-revalidate, max-age=0',
        'Pragma' => 'public',
        'X-Content-Type-Options' => 'nosniff',
    ]);
}

public function downloadFile(ReportUpload $reportUpload)
{
    if (!$reportUpload->path_file || !Storage::exists($reportUpload->path_file)) {
        abort(404, 'File tidak ditemukan.');
    }

    return response()->download(
        Storage::path($reportUpload->path_file),
        $reportUpload->nama_file
    );
}

private function parseNamaFileKendala(string $originalName, Request $request): ?array
{
    $filename = pathinfo($originalName, PATHINFO_FILENAME);

    $text = strtoupper($filename);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (!preg_match('/(\d{1,2})\s+(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER)/i', $text, $dateMatch)) {
        return null;
    }

    $hari = str_pad($dateMatch[1], 2, '0', STR_PAD_LEFT);
    $bulanAngka = $this->bulanIndonesiaKeAngka($dateMatch[2]);

    if (!$bulanAngka) {
        return null;
    }

    $tahun = $request->tahun ?: date('Y');

    if (preg_match('/\b(20\d{2})\b/', $text, $yearMatch)) {
        $tahun = $yearMatch[1];
    }

    $tanggal = $tahun . '-' . str_pad($bulanAngka, 2, '0', STR_PAD_LEFT) . '-' . $hari;

    if (!preg_match('/\b([A-Z]{1,2})\s*(\d{3,5})\s*([A-Z]{1,3})\b/', $text, $nopolMatch)) {
        return null;
    }

    $nopol = trim($nopolMatch[1] . ' ' . $nopolMatch[2] . ' ' . $nopolMatch[3]);

    $sisaSetelahNopol = $text;

    if (preg_match('/\b[A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3}\b\s*(.+)$/i', $text, $restMatch)) {
        $sisaSetelahNopol = trim($restMatch[1]);
    }

    [$tlpg, $eventRaw] = $this->ambilTlpgDanEventDariNamaFile($sisaSetelahNopol);

    if (!$tlpg || !$eventRaw) {
        return null;
    }

    return [
        'tanggal' => $tanggal,
        'nopol' => $this->normalisasiNopol($nopol),
        'tlpg' => $tlpg,
        'event_name' => $this->normalisasiText($eventRaw),
    ];
}

private function parseAccidentPdfBaru(string $text, string $originalName, Request $request): ?array
{
    $cleanText = $this->normalisasiText($text);
    $upperText = strtoupper($cleanText);
    $upperFile = strtoupper(pathinfo($originalName, PATHINFO_FILENAME));

    $tanggal = $this->ambilTanggalIndonesiaDariText($cleanText);

    if (!$tanggal) {
        $tanggal = $this->ambilTanggalIndonesiaDariText($originalName);
    }

    if (!$tanggal) {
        return null;
    }

    $jam = $this->ambilRegex('/\bJam\s*[:\-]?\s*([0-9]{1,2}[:\.][0-9]{2}(?::[0-9]{2})?)/i', $cleanText);

    if ($jam) {
        $jam = str_replace('.', ':', $jam);

        if (preg_match('/^\d{1,2}:\d{2}$/', $jam)) {
            $jam .= ':00';
        }
    }

    $nopol = $this->ambilRegex('/(?:No\s*Pol|Nopol|NoPol|NOPOL)\s*[:\-]?\s*([A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3})/i', $cleanText);

    if (!$nopol) {
        $nopol = $this->ambilRegex('/\b([A-Z]{1,2}\s*\d{3,5}\s*[A-Z]{1,3})\b/i', $originalName . ' ' . $cleanText);
    }

    if (!$nopol) {
        return null;
    }

    $typeAccident = null;

    if (preg_match('/\bLaka\s*[:\-]?\s*(AKTIF|PASIF)\b/i', $cleanText, $match)) {
        $typeAccident = strtoupper($match[1]);
    } elseif (str_contains($upperText, 'ACCIDENT AKTIF') || str_contains($upperText, 'LAKA AKTIF')) {
        $typeAccident = 'AKTIF';
    } elseif (str_contains($upperText, 'ACCIDENT PASIF') || str_contains($upperText, 'LAKA PASIF')) {
        $typeAccident = 'PASIF';
    }

    if (!$typeAccident) {
        return null;
    }

    $tlpg = $this->ambilTlpgAccidentDariText($cleanText . ' ' . $upperFile);

    if (!$tlpg) {
        return null;
    }

    $driver = $this->ambilRegex('/(?:Nama\s*AMT|Nama\s*Driver|Driver|AMT)\s*[:\-]?\s*([A-Z][A-Z\s\.\']{2,70})/i', $cleanText);

    if ($driver) {
        $driver = preg_replace('/\s+/', ' ', trim($driver));
    }

    $evidence = $this->ambilRegex('/Evidence\s*[:\-]?\s*(https?:\/\/\S+)/i', $cleanText);

    return [
        'tanggal' => $tanggal,
        'jam' => $jam,
        'nopol' => $this->normalisasiNopol($nopol),
        'driver_name' => $driver ? $this->normalisasiText($driver) : null,
        'tlpg' => $tlpg,
        'type_accident' => $typeAccident,
        'evidence' => $evidence,
    ];
}

private function ambilTanggalIndonesiaDariText(string $text): ?string
{
    if (!preg_match('/\b(\d{1,2})\s+(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|DESEMBER)\s+(\d{4})\b/i', $text, $match)) {
        return null;
    }

    $hari = str_pad($match[1], 2, '0', STR_PAD_LEFT);
    $bulan = $this->bulanIndonesiaKeAngka($match[2]);
    $tahun = $match[3];

    if (!$bulan) {
        return null;
    }

    return $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . $hari;
}

private function ambilTlpgAccidentDariText(string $text): ?string
{
    $text = strtoupper($text);
    $text = str_replace(['.', ',', '_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    $aliases = [
        'TLPG TJ PERAK' => 'TLPG TJ PERAK',
        'TERMINAL TJ PERAK' => 'TLPG TJ PERAK',
        'TJ PERAK' => 'TLPG TJ PERAK',

        'TLPG MEM GRESIK' => 'TLPG MEM GRESIK',
        'TERMINAL MEM GRESIK' => 'TLPG MEM GRESIK',
        'MEM GRESIK' => 'TLPG MEM GRESIK',

        'TLPG LOMBOK' => 'TLPG LOMBOK',
        'TLPG TJ LOMBOK' => 'TLPG LOMBOK',
        'TERMINAL LOMBOK' => 'TLPG LOMBOK',

        'TLPG MANGGIS' => 'TLPG MANGGIS',
        'TERMINAL MANGGIS' => 'TLPG MANGGIS',

        'TLPG BIMA' => 'TLPG BIMA',
        'TERMINAL BIMA' => 'TLPG BIMA',

        'TLPG TJ WANGI' => 'TLPG TJ WANGI',
        'TERMINAL TJ WANGI' => 'TLPG TJ WANGI',
        'TJ WANGI' => 'TLPG TJ WANGI',
    ];

    foreach ($aliases as $keyword => $canonical) {
        if (str_contains($text, $keyword)) {
            return $canonical;
        }
    }

    return null;
}

private function readCachedExcelCell(
    Worksheet $sheet,
    string $coordinate
): mixed {
    $cell = $sheet->getCell($coordinate);

    /*
     * File berasal dari Google Sheets.
     * Beberapa formula seperti XLOOKUP dan ARRAYFORMULA
     * belum tentu dapat dihitung ulang oleh PhpSpreadsheet.
     *
     * Karena itu gunakan cached value dari file Excel.
     */
    if ($cell->isFormula()) {
        $cachedValue = $cell->getOldCalculatedValue();

        if (
            $cachedValue !== null &&
            $cachedValue !== ''
        ) {
            return $cachedValue;
        }

        try {
            $calculatedValue = $cell->getCalculatedValue();

            if (
                $calculatedValue !== null &&
                $calculatedValue !== ''
            ) {
                return $calculatedValue;
            }
        } catch (\Throwable $e) {
            return null;
        }
    }

    return $cell->getValue();
}

private function parseErrorlogExcelDateTime(
    mixed $value
): ?Carbon {
    if ($value === null || $value === '') {
        return null;
    }

    try {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(
                \DateTime::createFromInterface($value)
            );
        }

        /*
         * Contoh nilai Excel:
         * 46163.598448101853
         *
         * Mengandung tanggal sekaligus jam.
         */
        if (is_numeric($value)) {
            return Carbon::instance(
                ExcelDate::excelToDateTimeObject(
                    (float) $value
                )
            );
        }

        return Carbon::parse(
            trim((string) $value)
        );
    } catch (\Throwable $e) {
        return null;
    }
}
}