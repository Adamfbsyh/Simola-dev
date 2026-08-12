<?php

namespace App\Http\Controllers;

use App\Models\ErrorlogSheetSource;
use App\Services\GoogleErrorlogSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ErrorlogSheetController extends Controller
{
    public function index()
    {
        $sources = ErrorlogSheetSource::query()
            ->with('creator:id,name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);

        return view(
            'errorlog-sheet.index',
            compact('sources')
        );
    }

    public function store(
        Request $request,
        GoogleErrorlogSyncService $service
    ) {
        $validated = $request->validate([
            'spreadsheet_url' => [
                'required',
                'url',
                'max:1000',
            ],

            'period' => [
                'required',
                'date_format:Y-m',
            ],

            'sheet_name' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        $spreadsheetId = $this->extractSpreadsheetId(
            $validated['spreadsheet_url']
        );

        if (!$spreadsheetId) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Spreadsheet ID tidak ditemukan pada link.'
                );
        }

        $period = Carbon::createFromFormat(
            'Y-m',
            $validated['period']
        );

        $source = ErrorlogSheetSource::updateOrCreate(
            [
                'spreadsheet_id' =>
                    $spreadsheetId,

                'sheet_name' =>
                    trim($validated['sheet_name']),

                'year' =>
                    $period->year,

                'month' =>
                    $period->month,
            ],
            [
                'spreadsheet_url' =>
                    $validated['spreadsheet_url'],

                'created_by' =>
                    auth()->id(),

                'status' =>
                    'proses',

                'last_error' =>
                    null,
            ]
        );

        try {
            $stats = $service->sync(
                $request->user(),
                $source
            );

            return redirect()
                ->route('errorlog-sheet.index')
                ->with(
                    'success',
                    sprintf(
                        'Sinkronisasi berhasil. Total %d data: %d baru, %d diperbarui, %d tidak berubah, dan %d dilewati.',
                        $stats['total'],
                        $stats['created'],
                        $stats['updated'],
                        $stats['unchanged'],
                        $stats['skipped']
                    )
                );
        } catch (\Throwable $e) {
            report($e);

            $source->update([
                'status' => 'gagal',
                'last_error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('errorlog-sheet.index')
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function sync(
        Request $request,
        ErrorlogSheetSource $source,
        GoogleErrorlogSyncService $service
    ) {
        try {
            $source->update([
                'status' => 'proses',
                'last_error' => null,
            ]);

            $stats = $service->sync(
                $request->user(),
                $source
            );

            return back()->with(
                'success',
                sprintf(
                    'Sinkronisasi ulang berhasil. Total %d data: %d baru, %d diperbarui, dan %d tidak berubah.',
                    $stats['total'],
                    $stats['created'],
                    $stats['updated'],
                    $stats['unchanged']
                )
            );
        } catch (\Throwable $e) {
            report($e);

            $source->update([
                'status' => 'gagal',
                'last_error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function destroy(
        ErrorlogSheetSource $source
    ) {
        /*
         * Karena foreign key cascade,
         * data monitoring_events dari sumber ini ikut dihapus.
         */
        $source->delete();

        return back()->with(
            'success',
            'Sumber spreadsheet dan data Errorlog terkait berhasil dihapus.'
        );
    }

    private function extractSpreadsheetId(
        string $url
    ): ?string {
        if (
            preg_match(
                '#/spreadsheets/d/([A-Za-z0-9_-]+)#',
                $url,
                $match
            )
        ) {
            return $match[1];
        }

        return null;
    }
}