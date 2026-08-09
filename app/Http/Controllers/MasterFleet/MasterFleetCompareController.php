<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\MasterFleetCompareBatch;
use App\Services\MasterFleet\MasterFleetCompareApplyService;
use App\Services\MasterFleet\MasterFleetExcelCompareService;
use App\Support\MasterFleet\FleetType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MasterFleetCompareController extends Controller
{
    public function index(): View
    {
        return view('master-fleet.compare.index', [
            'batches' => MasterFleetCompareBatch::query()
                ->orderByDesc('id')
                ->paginate(20),
            'fleetTypes' => FleetType::options(),
            'currentFleetType' => FleetType::current(),
        ]);
    }

    public function upload(
        Request $request,
        MasterFleetExcelCompareService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'spreadsheet' => ['required','file','max:20480','mimes:xlsx,xls,csv'],
            'fleet_type' => ['required', Rule::in(array_keys(FleetType::options()))],
        ], [
            'spreadsheet.required' => 'Pilih file Excel dari pengawas.',
            'spreadsheet.mimes' => 'Format file harus XLSX, XLS, atau CSV.',
            'spreadsheet.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $file = $request->file('spreadsheet');

        if (!$file || !$file->isValid()) {
            return back()->with('error', 'File gagal diunggah.');
        }

        $uuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs(
            'master-fleet-compare',
            $uuid . '.' . $extension,
            'local'
        );

        if (!$storedPath) {
            return back()->with('error', 'File staging tidak dapat disimpan.');
        }

        try {
            $temporaryPath = $file->getRealPath();
            $batch = MasterFleetCompareBatch::query()->create([
                'uuid' => $uuid,
                'fleet_type' => FleetType::normalize($validated['fleet_type']),
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'source_hash' => ($temporaryPath && is_file($temporaryPath))
                    ? (hash_file('sha256', $temporaryPath) ?: hash('sha256', $uuid))
                    : hash('sha256', $uuid),
                'status' => 'processing',
                'uploaded_by' => $request->user()?->id,
            ]);

            $service->compare($batch);

            return redirect()
                ->route('master-fleet.compare.show', $batch)
                ->with('success', 'Compare selesai. Master Fleet aktif belum diubah.');
        } catch (Throwable $e) {
            report($e);
            Storage::disk('local')->delete($storedPath);

            if (isset($batch)) {
                $batch->delete();
            }

            return back()
                ->withInput()
                ->with('error', app()->isLocal()
                    ? $e->getMessage()
                    : 'File gagal dibandingkan.');
        }
    }

    public function show(
        Request $request,
        MasterFleetCompareBatch $batch
    ): View {
        $status = trim((string) $request->query('status', ''));

        $query = $batch->rows()
            ->orderByRaw(
                "CASE status
                    WHEN 'review' THEN 1
                    WHEN 'plate_change' THEN 2
                    WHEN 'changed' THEN 3
                    WHEN 'new' THEN 4
                    WHEN 'missing' THEN 5
                    WHEN 'same' THEN 6
                    ELSE 7 END"
            )
            ->orderBy('plate_number')
            ->orderBy('source_row');

        $allowed = ['same','changed','plate_change','new','missing','review','applyable'];

        if (in_array($status, $allowed, true)) {
            if ($status === 'applyable') {
                $query->where('can_apply', true);
            } else {
                $query->where('status', $status);
            }
        }

        return view('master-fleet.compare.show', [
            'batch' => $batch,
            'rows' => $query->paginate(100)->withQueryString(),
            'status' => $status,
            'summary' => $batch->summary ?? [],
        ]);
    }

    public function apply(
        Request $request,
        MasterFleetCompareBatch $batch,
        MasterFleetCompareApplyService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'rows' => ['required','array','min:1'],
            'rows.*' => ['integer'],
        ]);

        try {
            $result = $service->apply(
                batch: $batch,
                rowIds: $validated['rows'],
                userId: (int) $request->user()->id
            );

            return redirect()
                ->route('master-fleet.compare.show', $batch)
                ->with(
                    'success',
                    'Perubahan diterapkan: '
                    . $result['updated'] . ' diperbarui, '
                    . $result['created'] . ' kendaraan baru, '
                    . $result['plateChanged'] . ' ganti Nopol. '
                    . 'Sisa belum diterapkan: ' . $result['remaining'] . '.'
                );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                app()->isLocal()
                    ? $e->getMessage()
                    : 'Perubahan gagal diterapkan. Tidak ada perubahan parsial disimpan.'
            );
        }
    }

    public function download(
        MasterFleetCompareBatch $batch
    ): StreamedResponse {
        abort_unless(
            Storage::disk('local')->exists($batch->stored_path),
            404
        );

        return Storage::disk('local')
            ->download($batch->stored_path, $batch->original_name);
    }

    public function export(
        MasterFleetCompareBatch $batch
    ): BinaryFileResponse {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('HASIL COMPARE');

        $sheet->fromArray([
            'Status','Baris Excel','Nopol','Unit Code','Bisa Diterapkan',
            'Status Apply','Perbedaan','Data Master','Data Pengawas'
        ], null, 'A1');

        $line = 2;

        $batch->rows()
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($sheet, &$line): void {
                foreach ($rows as $row) {
                    $sheet->fromArray([
                        $this->statusLabel($row->status),
                        $row->source_row,
                        $row->plate_number,
                        $row->unit_code,
                        $row->can_apply ? 'YA' : 'TIDAK',
                        $row->apply_status,
                        $this->jsonText($row->diff_data),
                        $this->jsonText($row->current_data),
                        $this->jsonText($row->source_data),
                    ], null, 'A' . $line);

                    $line++;
                }
            });

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:I' . max(1, $line - 1));

        foreach (['A'=>22,'B'=>12,'C'=>18,'D'=>18,'E'=>16,'F'=>16,'G'=>45,'H'=>45,'I'=>45] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->getStyle('A1:I' . max(2, $line - 1))
            ->getAlignment()
            ->setVertical('top')
            ->setWrapText(true);

        $tmp = tempnam(sys_get_temp_dir(), 'simola-compare-');

        if ($tmp === false) {
            throw new RuntimeException('File sementara export gagal dibuat.');
        }

        (new Xlsx($book))->save($tmp);
        $book->disconnectWorksheets();

        $filename = 'COMPARE_'
            . strtoupper($batch->fleet_type)
            . '_'
            . pathinfo($batch->original_name, PATHINFO_FILENAME)
            . '.xlsx';

        return response()->download($tmp, $filename)->deleteFileAfterSend(true);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'same' => 'SAMA',
            'changed' => 'BERUBAH',
            'plate_change' => 'KEMUNGKINAN GANTI NOPOL',
            'new' => 'DATA BARU',
            'missing' => 'TIDAK ADA DI FILE BARU',
            'review' => 'PERLU REVIEW',
            default => mb_strtoupper($status, 'UTF-8'),
        };
    }

    private function jsonText(?array $value): string
    {
        if (!$value) return '';

        return (string) json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}
