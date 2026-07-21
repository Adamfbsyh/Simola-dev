<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class LaporanController extends Controller
{
    public function index()
    {
        $laporans = Laporan::latest()->paginate(10);

        return view('laporan.index', compact('laporans'));
    }

    public function upload()
    {
        return view('laporan.upload');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $file = $request->file('file_excel');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return back()->with('error', 'File Excel kosong atau formatnya belum sesuai.');
        }

        $headers = [];

        foreach ($rows[1] as $column => $value) {
            $headers[$column] = strtolower(str_replace(' ', '_', trim((string) $value)));
        }

        foreach ($rows as $index => $row) {
            if ($index === 1) {
                continue;
            }

            if (empty(array_filter($row))) {
                continue;
            }

            $data = [];

            foreach ($headers as $column => $headerName) {
                $data[$headerName] = $row[$column] ?? null;
            }

            Laporan::create([
                'tanggal'     => $this->formatTanggal($data['tanggal'] ?? null),
                'periode'     => $data['periode'] ?? null,
                'unit'        => $data['unit'] ?? null,
                'kategori'    => $data['kategori'] ?? null,
                'target'      => (int) ($data['target'] ?? 0),
                'realisasi'   => (int) ($data['realisasi'] ?? 0),
                'status'      => $data['status'] ?? null,
                'kendala'     => $data['kendala'] ?? null,
                'keterangan'  => $data['keterangan'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('laporan.index')
            ->with('success', 'Data laporan berhasil diupload dan disimpan.');
    }

    private function formatTanggal($tanggal)
    {
        if (!$tanggal) {
            return null;
        }

        try {
            if (is_numeric($tanggal)) {
                return ExcelDate::excelToDateTimeObject($tanggal)->format('Y-m-d');
            }

            return Carbon::parse($tanggal)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}