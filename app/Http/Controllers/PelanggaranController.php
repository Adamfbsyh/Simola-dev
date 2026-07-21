<?php

namespace App\Http\Controllers;

use App\Models\LaporanFile;
use App\Models\Pelanggaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\AmtNameExtractor;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $terminalOptions = Pelanggaran::whereNotNull('terminal')
            ->select('terminal')
            ->distinct()
            ->orderBy('terminal')
            ->pluck('terminal');

        $kategoriOptions = Pelanggaran::whereNotNull('kategori_sanksi')
            ->select('kategori_sanksi')
            ->distinct()
            ->orderBy('kategori_sanksi')
            ->pluck('kategori_sanksi');

        $query = Pelanggaran::with('laporanFile');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nopol', 'like', "%{$search}%")
                    ->orWhere('terminal', 'like', "%{$search}%")
                    ->orWhere('driver', 'like', "%{$search}%")
                    ->orWhere('jenis_pelanggaran', 'like', "%{$search}%")
                    ->orWhere('evidence', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_laporan', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_laporan', '<=', $request->tanggal_sampai);
        }

        if ($request->filled('terminal')) {
            $query->where('terminal', $request->terminal);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori_sanksi', $request->kategori);
        }

        $pelanggarans = $query
            ->latest()
            ->paginate(25)
            ->appends($request->query());

        $totalData = $query->count();

        return view('pelanggaran.index', compact(
            'pelanggarans',
            'terminalOptions',
            'kategoriOptions',
            'totalData'
        ));
    }

    public function upload()
    {
        return view('pelanggaran.upload');
    }

    public function import(Request $request)
    {
        $request->validate([
            'tanggal_laporan' => 'required|date',
            'periode' => 'required|string',
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file_excel');
        $fileHash = hash_file('sha256', $file->getRealPath());

        $cekDuplikat = LaporanFile::whereDate('tanggal_laporan', $request->tanggal_laporan)
            ->where(function ($query) use ($fileHash, $file) {
                $query->where('file_hash', $fileHash)
                    ->orWhere('nama_file', $file->getClientOriginalName());
            })
            ->first();

        if ($cekDuplikat) {
            return back()->with('error', 'File ini sudah pernah diimport pada tanggal laporan yang sama. Data tidak dimasukkan ulang agar tidak dobel.');
        }

        DB::beginTransaction();

        try {
            $path = $file->store('laporan-excel');

            $laporanFile = LaporanFile::create([
                'nama_file' => $file->getClientOriginalName(),
                'path_file' => $path,
                'file_hash' => $fileHash,
                'jenis_laporan' => 'K3-02.2',
                'tanggal_laporan' => $request->tanggal_laporan,
                'periode' => $request->periode,
                'uploaded_by' => auth()->id(),
            ]);

            $spreadsheet = IOFactory::load(Storage::path($path));
            $sheet = $spreadsheet->getSheetByName('K3-02.2');

            if (!$sheet) {
                DB::rollBack();
                return back()->with('error', 'Sheet K3-02.2 tidak ditemukan di file Excel.');
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

                    Pelanggaran::create([
                        'laporan_file_id' => $laporanFile->id,
                        'tanggal_laporan' => $request->tanggal_laporan,
                        'no_urut' => is_numeric($noUrut) ? (int) $noUrut : null,
                        'nopol' => $nopol,
                        'terminal' => $terminal,
                        'driver' => $driver,
                        'kategori_sanksi' => $info['kategori'],
                        'jenis_pelanggaran' => $info['jenis'],
                        'nilai' => is_numeric($nilai) ? (int) $nilai : 1,
                        'evidence' => $evidence,
                        'row_excel' => $row,
                    ]);

                    $totalImport++;
                }
            }

            DB::commit();

            return redirect()
                ->route('pelanggaran.index')
                ->with('success', 'Import berhasil. Total pelanggaran masuk: ' . $totalImport);

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
{
    $query = Pelanggaran::with('laporanFile');

    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('nopol', 'like', "%{$search}%")
                ->orWhere('terminal', 'like', "%{$search}%")
                ->orWhere('driver', 'like', "%{$search}%")
                ->orWhere('jenis_pelanggaran', 'like', "%{$search}%")
                ->orWhere('evidence', 'like', "%{$search}%");
        });
    }

    if ($request->filled('tanggal_dari')) {
        $query->whereDate('tanggal_laporan', '>=', $request->tanggal_dari);
    }

    if ($request->filled('tanggal_sampai')) {
        $query->whereDate('tanggal_laporan', '<=', $request->tanggal_sampai);
    }

    if ($request->filled('terminal')) {
        $query->where('terminal', $request->terminal);
    }

    if ($request->filled('kategori')) {
        $query->where('kategori_sanksi', $request->kategori);
    }

    $data = $query->orderBy('tanggal_laporan', 'desc')->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setTitle('Data Pelanggaran');

    $headers = [
        'No',
        'Tanggal Laporan',
        'NOPOL',
        'Terminal',
        'Driver',
        'Kategori Sanksi',
        'Jenis Pelanggaran',
        'Evidence',
        'Row Excel',
        'Nama File',
    ];

    $column = 'A';

    foreach ($headers as $header) {
        $sheet->setCellValue($column . '1', $header);
        $column++;
    }

    $rowNumber = 2;
    $no = 1;

    foreach ($data as $item) {
        $sheet->setCellValue('A' . $rowNumber, $no++);
        $sheet->setCellValue('B' . $rowNumber, $item->tanggal_laporan);
        $sheet->setCellValue('C' . $rowNumber, $item->nopol);
        $sheet->setCellValue('D' . $rowNumber, $item->terminal);
        $sheet->setCellValue('E' . $rowNumber, $item->driver);
        $sheet->setCellValue('F' . $rowNumber, $item->kategori_sanksi);
        $sheet->setCellValue('G' . $rowNumber, $item->jenis_pelanggaran);
        $sheet->setCellValue('H' . $rowNumber, $item->evidence);
        $sheet->setCellValue('I' . $rowNumber, $item->row_excel);
        $sheet->setCellValue('J' . $rowNumber, $item->laporanFile->nama_file ?? '-');

        $rowNumber++;
    }

    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $fileName = 'data-pelanggaran-' . date('Ymd-His') . '.xlsx';
    $filePath = storage_path('app/public/' . $fileName);

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    return response()->download($filePath)->deleteFileAfterSend(true);
}

    public function files()
    {
        $files = LaporanFile::withCount('pelanggarans')
            ->latest()
            ->paginate(10);

        return view('pelanggaran.files', compact('files'));
    }

    public function destroyFile(LaporanFile $laporanFile)
    {
        if ($laporanFile->path_file && Storage::exists($laporanFile->path_file)) {
            Storage::delete($laporanFile->path_file);
        }

        $laporanFile->delete();

        return redirect()
            ->route('pelanggaran.files')
            ->with('success', 'File laporan dan seluruh data pelanggarannya berhasil dihapus.');
    }

    private function kolomPelanggaran(): array
    {
        return [
            'H' => [
                'kategori' => 'SP 1',
                'jenis' => 'Menerima Penumpang Selain AMT',
            ],
            'I' => [
                'kategori' => 'SP 1',
                'jenis' => 'Mengemudi Lebih dari 4 Jam',
            ],
            'J' => [
                'kategori' => 'SP 1',
                'jenis' => 'Over Speed',
            ],
            'K' => [
                'kategori' => 'SP 1',
                'jenis' => 'Perlambatan Mendadak',
            ],
            'L' => [
                'kategori' => 'SP 1',
                'jenis' => 'Akselerasi Mendadak',
            ],
            'M' => [
                'kategori' => 'SP 1',
                'jenis' => 'Tikungan Tajam',
            ],
            'N' => [
                'kategori' => 'SP 1',
                'jenis' => 'Melebihi Batas Waktu Parkir',
            ],
            'O' => [
                'kategori' => 'SP 1',
                'jenis' => 'Seat Belt',
            ],

            'P' => [
                'kategori' => 'SP 2',
                'jenis' => 'Keluar Rute',
            ],

            'Q' => [
                'kategori' => 'SP 3',
                'jenis' => 'Berganti AMT Tanpa Lisensi',
            ],
            'R' => [
                'kategori' => 'SP 3',
                'jenis' => 'Menggunakan Handphone / Gadget',
            ],

            'S' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Merokok / Vape',
            ],
            'T' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Menutup / Mengubah Posisi CAM',
            ],
            'U' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Merusak / Melepas Device GPS / CAM',
            ],
            'V' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Pengurangan Bahan Bakar',
            ],
            'W' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Berganti AMT Tidak Berlisensi Accident',
            ],
            'X' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Pengemudi Kelelahan Accident',
            ],
            'Y' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Mengemudi Tidak Baik Napza / Alkohol',
            ],
            'Z' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Menghilangkan Sinyal GPS Jammer',
            ],
            'AA' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Geolokasi Blackzone & Redzone',
            ],
            'AB' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Pelecehan Verbal',
            ],
            'AC' => [
                'kategori' => 'PENGEMBALIAN',
                'jenis' => 'Mengintervensi / Mengancam / Bekerja Sama Dengan Petugas RTC',
            ],
        ];
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
}