<?php

namespace App\Services\MasterFleet;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class SpreadsheetPreviewService
{
    private const MAX_PREVIEW_ROWS = 25;

    private const MAX_PREVIEW_COLUMNS = 20;

    private const MAX_FORMULA_EXAMPLES = 15;

    private const MAX_ERROR_EXAMPLES = 15;

    private const MAX_SCANNED_CELLS = 100000;

    /**
     * Membaca workbook tanpa menyimpan data ke database.
     */
    public function preview(
        UploadedFile $file
    ): array {
        $path = $file->getRealPath();

        if (
            !$path
            ||
            !is_file($path)
        ) {
            throw new RuntimeException(
                'File upload tidak ditemukan.'
            );
        }

        $readerType =
            IOFactory::identify($path);

        $reader =
            IOFactory::createReader(
                $readerType
            );

        /*
        |--------------------------------------------------------------------------
        | Formula tetap dibaca
        |--------------------------------------------------------------------------
        |
        | false berarti formula asli tetap tersedia.
        | Kita juga mencoba membaca nilai hasil terakhirnya.
        |
        */

        $reader->setReadDataOnly(false);

        if (
            method_exists(
                $reader,
                'setReadEmptyCells'
            )
        ) {
            $reader->setReadEmptyCells(
                false
            );
        }

        if (
            method_exists(
                $reader,
                'setIncludeCharts'
            )
        ) {
            $reader->setIncludeCharts(
                false
            );
        }

        $spreadsheet =
            $reader->load($path);

        $sheets = [];

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $worksheet
        ) {
            $sheets[] =
                $this->previewWorksheet(
                    $worksheet
                );
        }

        $result = [
            'file_name' =>
                $file->getClientOriginalName(),

            'extension' =>
                strtolower(
                    $file->getClientOriginalExtension()
                ),

            'file_size' =>
                (int) $file->getSize(),

            'file_size_label' =>
                $this->formatBytes(
                    (int) $file->getSize()
                ),

            'reader_type' =>
                $readerType,

            'sheet_count' =>
                count($sheets),

            'sheets' =>
                $sheets,
        ];

        $spreadsheet
            ->disconnectWorksheets();

        unset($spreadsheet);

        return $result;
    }

    /**
     * Membaca ringkasan satu worksheet.
     */
    private function previewWorksheet(
        Worksheet $worksheet
    ): array {
        $highestRow =
            max(
                1,
                $worksheet
                    ->getHighestDataRow()
            );

        $highestColumn =
            $worksheet
                ->getHighestDataColumn();

        $highestColumnIndex =
            max(
                1,
                Coordinate::columnIndexFromString(
                    $highestColumn
                )
            );

        $previewRowCount =
            min(
                $highestRow,
                self::MAX_PREVIEW_ROWS
            );

        $previewColumnCount =
            min(
                $highestColumnIndex,
                self::MAX_PREVIEW_COLUMNS
            );

        $headers = [];

        for (
            $columnIndex = 1;
            $columnIndex <= $previewColumnCount;
            $columnIndex++
        ) {
            $headers[] =
                Coordinate::stringFromColumnIndex(
                    $columnIndex
                );
        }

        $rows = [];

        for (
            $rowNumber = 1;
            $rowNumber <= $previewRowCount;
            $rowNumber++
        ) {
            $cells = [];

            for (
                $columnIndex = 1;
                $columnIndex <= $previewColumnCount;
                $columnIndex++
            ) {
                $columnLetter =
                    Coordinate::stringFromColumnIndex(
                        $columnIndex
                    );

                $coordinate =
                    $columnLetter
                    .
                    $rowNumber;

                $cell =
                    $worksheet->getCell(
                        $coordinate
                    );

                $cells[$columnLetter] =
                    $this->readCell(
                        $cell
                    );
            }

            $rows[] = [
                'row_number' =>
                    $rowNumber,

                'cells' =>
                    $cells,
            ];
        }

        $statistics =
            $this->scanWorksheet(
                $worksheet
            );

        return [
            'name' =>
                $worksheet->getTitle(),

            'state' =>
                $worksheet->getSheetState(),

            'is_visible' =>
                $worksheet->getSheetState()
                ===
                Worksheet::SHEETSTATE_VISIBLE,

            'highest_row' =>
                $highestRow,

            'highest_column' =>
                $highestColumn,

            'highest_column_index' =>
                $highestColumnIndex,

            'preview_row_count' =>
                $previewRowCount,

            'preview_column_count' =>
                $previewColumnCount,

            'preview_truncated' =>
                $highestRow
                    >
                    self::MAX_PREVIEW_ROWS
                ||
                $highestColumnIndex
                    >
                    self::MAX_PREVIEW_COLUMNS,

            'headers' =>
                $headers,

            'rows' =>
                $rows,

            'statistics' =>
                $statistics,

            'likely_source' =>
                $this->detectLikelySource(
                    $worksheet->getTitle()
                ),
        ];
    }

    /**
     * Membaca nilai satu cell.
     */
    private function readCell(
        mixed $cell
    ): array {
        $rawValue =
            $cell->getValue();

        $isFormula =
            $cell->getDataType()
                ===
                DataType::TYPE_FORMULA
            ||
            (
                is_string($rawValue)
                &&
                str_starts_with(
                    $rawValue,
                    '='
                )
            );

        $calculationFailed = false;

        $calculationMessage = null;

        if ($isFormula) {
            try {
                $displayValue =
                    $cell->getCalculatedValue();
            } catch (Throwable $e) {
                $calculationFailed = true;

                $calculationMessage =
                    $e->getMessage();

                $displayValue =
                    method_exists(
                        $cell,
                        'getOldCalculatedValue'
                    )
                        ? $cell
                            ->getOldCalculatedValue()
                        : null;
            }
        } else {
            try {
                $displayValue =
                    $cell->getFormattedValue();
            } catch (Throwable) {
                $displayValue =
                    $rawValue;
            }
        }

        $display =
            $this->valueToString(
                $displayValue
            );

        $raw =
            $this->valueToString(
                $rawValue
            );

        $isError =
            $cell->getDataType()
                ===
                DataType::TYPE_ERROR
            ||
            (
                $display !== ''
                &&
                str_starts_with(
                    $display,
                    '#'
                )
            );

        return [
            'coordinate' =>
                $cell->getCoordinate(),

            'display' =>
                $display,

            'raw' =>
                $raw,

            'data_type' =>
                $cell->getDataType(),

            'is_formula' =>
                $isFormula,

            'formula' =>
                $isFormula
                    ? $raw
                    : null,

            'is_error' =>
                $isError,

            'calculation_failed' =>
                $calculationFailed,

            'calculation_message' =>
                $calculationMessage,
        ];
    }

    /**
     * Memindai formula dan error pada worksheet.
     */
    private function scanWorksheet(
        Worksheet $worksheet
    ): array {
        $formulaCount = 0;

        $errorCount = 0;

        $nonEmptyCount = 0;

        $scannedCellCount = 0;

        $scanTruncated = false;

        $formulaExamples = [];

        $errorExamples = [];

        $coordinates =
            $worksheet
                ->getCellCollection()
                ->getCoordinates();

        foreach (
            $coordinates
            as $coordinate
        ) {
            if (
                $scannedCellCount
                >=
                self::MAX_SCANNED_CELLS
            ) {
                $scanTruncated = true;

                break;
            }

            $scannedCellCount++;

            $cell =
                $worksheet->getCell(
                    $coordinate
                );

            $cellData =
                $this->readCell(
                    $cell
                );

            if (
                $cellData['raw'] !== ''
                ||
                $cellData['display'] !== ''
            ) {
                $nonEmptyCount++;
            }

            if (
                $cellData['is_formula']
            ) {
                $formulaCount++;

                if (
                    count($formulaExamples)
                    <
                    self::MAX_FORMULA_EXAMPLES
                ) {
                    $formulaExamples[] = [
                        'coordinate' =>
                            $coordinate,

                        'formula' =>
                            $cellData['formula'],

                        'result' =>
                            $cellData['display'],

                        'calculation_failed' =>
                            $cellData[
                                'calculation_failed'
                            ],
                    ];
                }
            }

            if (
                $cellData['is_error']
                ||
                $cellData[
                    'calculation_failed'
                ]
            ) {
                $errorCount++;

                if (
                    count($errorExamples)
                    <
                    self::MAX_ERROR_EXAMPLES
                ) {
                    $errorExamples[] = [
                        'coordinate' =>
                            $coordinate,

                        'value' =>
                            $cellData['display'],

                        'formula' =>
                            $cellData['formula'],

                        'message' =>
                            $cellData[
                                'calculation_message'
                            ],
                    ];
                }
            }
        }

        return [
            'non_empty_count' =>
                $nonEmptyCount,

            'formula_count' =>
                $formulaCount,

            'error_count' =>
                $errorCount,

            'scanned_cell_count' =>
                $scannedCellCount,

            'scan_truncated' =>
                $scanTruncated,

            'formula_examples' =>
                $formulaExamples,

            'error_examples' =>
                $errorExamples,
        ];
    }

    /**
     * Mengenali sheet yang kemungkinan menjadi sumber Master Fleet.
     */
    private function detectLikelySource(
        string $sheetName
    ): ?string {
        $normalized =
            mb_strtoupper(
                trim($sheetName),
                'UTF-8'
            );

        if (
            str_contains(
                $normalized,
                'MASTER TLPG'
            )
        ) {
            return 'master_terminal';
        }

        if (
            str_contains(
                $normalized,
                'MASTER PERUSAHAAN'
            )
            ||
            str_contains(
                $normalized,
                'MASTER SPBE'
            )
        ) {
            return 'master_company';
        }

        if (
            str_contains(
                $normalized,
                'PC SET UTAMA'
            )
        ) {
            return 'master_vehicle';
        }

        if (
            str_contains(
                $normalized,
                'ROTASI'
            )
        ) {
            return 'rotation_result';
        }

        if (
            str_contains(
                $normalized,
                'NOTED'
            )
            ||
            str_contains(
                $normalized,
                'PERHITUNGAN'
            )
        ) {
            return 'calculation_helper';
        }

        return null;
    }

    /**
     * Mengubah berbagai jenis nilai menjadi string.
     */
    private function valueToString(
        mixed $value
    ): string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return '';
        }

        if (
            is_bool($value)
        ) {
            return $value
                ? 'TRUE'
                : 'FALSE';
        }

        if (
            is_scalar($value)
        ) {
            return trim(
                (string) $value
            );
        }

        $encoded =
            json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
            );

        return is_string($encoded)
            ? $encoded
            : '';
    }

    /**
     * Menampilkan ukuran file.
     */
    private function formatBytes(
        int $bytes
    ): string {
        if (
            $bytes < 1024
        ) {
            return $bytes . ' B';
        }

        if (
            $bytes < 1024 * 1024
        ) {
            return number_format(
                $bytes / 1024,
                2,
                ',',
                '.'
            ) . ' KB';
        }

        return number_format(
            $bytes / 1024 / 1024,
            2,
            ',',
            '.'
        ) . ' MB';
    }
}