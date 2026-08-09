<?php

namespace App\Http\Controllers\MasterFleet;

use App\Http\Controllers\Controller;
use App\Models\FleetGoogleAccount;
use App\Services\MasterFleet\MonthlyErrorLogActivityLog;
use App\Services\MasterFleet\MonthlyErrorLogGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MonthlyErrorLogGeneratorController extends Controller
{
    public function __construct(
        private readonly MonthlyErrorLogGeneratorService $service,
        private readonly MonthlyErrorLogActivityLog $activityLog
    ) {
    }

    public function index(Request $request): View
    {
        $k302Account = FleetGoogleAccount::query()
            ->where('user_id', $request->user()->id)
            ->where('purpose', FleetGoogleAccount::PURPOSE_K302)
            ->first();

        $rootFolderId = trim((string) config(
            'errorlog-monthly.root_folder_id',
            ''
        ));

        $templateSpreadsheetId = trim((string) config(
            'errorlog-monthly.template_spreadsheet_id',
            ''
        ));

        return view('master-fleet.errorlog-monthly.index', [
            'k302Account' => $k302Account,
            'defaultMonth' => now()->format('Y-m'),
            'rootFolderId' => $rootFolderId,
            'templateSpreadsheetId' => $templateSpreadsheetId,
            'templateUrl' => $templateSpreadsheetId !== ''
                ? 'https://docs.google.com/spreadsheets/d/'
                    . $templateSpreadsheetId . '/edit'
                : null,
            'periodCell' => trim((string) config(
                'errorlog-monthly.rekap_sheet',
                'REKAP'
            )) . '!' . strtoupper(trim((string) config(
                'errorlog-monthly.period_cell',
                'B1'
            ))),
            'recentActivities' => $this->activityLog->recent(),
            'canGenerate' => $this->canGenerate($request),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'root_folder' => ['required', 'string', 'max:500'],
        ]);

        try {
            $month = Carbon::createFromFormat(
                'Y-m',
                (string) $validated['month']
            )->startOfMonth();

            $result = $this->service->lookup(
                $request->user(),
                $month,
                (string) $validated['root_folder']
            );

            return response()->json([
                'ok' => true,
                'exists' => (bool) $result['exists'],
                'message' => $result['exists']
                    ? 'Spreadsheet bulan ini sudah ada.'
                    : 'Spreadsheet bulan ini belum dibuat.',
                'data' => $result,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'exists' => null,
                'message' => $this->compactErrorMessage($exception),
            ], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            $this->canGenerate($request),
            403,
            'Akun ini tidak memiliki akses untuk membuat Error Log bulanan.'
        );

        $validated = $request->validate([
            'month' => [
                'required',
                'date_format:Y-m',
            ],
            'root_folder' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'month.required' => 'Bulan wajib dipilih.',
            'month.date_format' => 'Format bulan harus YYYY-MM.',
            'root_folder.required' => 'Folder root Google Drive wajib diisi.',
        ]);

        try {
            $month = Carbon::createFromFormat(
                'Y-m',
                (string) $validated['month']
            )->startOfMonth();

            $result = $this->service->generate(
                $request->user(),
                $month,
                (string) $validated['root_folder']
            );

            $this->activityLog->recordSuccess($request->user(), $result);

            $message = $result['created']
                ? 'Spreadsheet Error Log bulanan berhasil dibuat.'
                : 'Spreadsheet untuk root + bulan tersebut sudah ada; tidak dibuat duplikat.';

            return to_route('master-fleet.errorlog-monthly.index')
                ->with($result['created'] ? 'success' : 'warning', $message)
                ->with('generated_errorlog', $result);
        } catch (Throwable $exception) {
            report($exception);

            $this->activityLog->recordFailure(
                $request->user(),
                isset($validated['month']) ? (string) $validated['month'] : null,
                isset($validated['root_folder']) ? (string) $validated['root_folder'] : null,
                $exception
            );

            return to_route('master-fleet.errorlog-monthly.index')
                ->withInput()
                ->with(
                    'error',
                    $this->compactErrorMessage($exception)
                );
        }
    }

    private function canGenerate(Request $request): bool
    {
        /*
         * Route generator sudah dilindungi oleh:
         * auth + verified + can:master-fleet.view.
         *
         * Jadi pengguna yang berhasil membuka halaman ini memang
         * sudah memiliki akses Master Fleet yang diperlukan.
         */
        return $request->user() !== null;
    }

    private function compactErrorMessage(Throwable $exception): string
    {
        $message = trim((string) preg_replace(
            '/\s+/u',
            ' ',
            $exception->getMessage()
        ));

        if ($message === '') {
            return 'Generator Error Log bulanan gagal diproses.';
        }

        return mb_strlen($message, 'UTF-8') > 1200
            ? mb_substr($message, 0, 1197, 'UTF-8') . '...'
            : $message;
    }
}
