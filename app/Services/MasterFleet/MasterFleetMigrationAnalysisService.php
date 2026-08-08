<?php

namespace App\Services\MasterFleet;

use App\Models\FleetVehicle;
use App\Support\CoordinateParser;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class MasterFleetMigrationAnalysisService
{
    private const MAX_DETAIL_ROWS = 500;

    /**
     * Menganalisis workbook tanpa memasukkan data
     * ke tabel master maupun grouping.
     */
    public function analyze(
        UploadedFile $file
    ): array {
        $path = $file->getRealPath();

        if (
            !$path
            ||
            !is_file($path)
        ) {
            throw new RuntimeException(
                'File spreadsheet tidak ditemukan.'
            );
        }

        $readerType =
            IOFactory::identify($path);

        $reader =
            IOFactory::createReader(
                $readerType
            );

        $reader->setReadDataOnly(
            false
        );

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

        try {
            $terminalSheet =
                $this->findSheet(
                    $spreadsheet,
                    [
                        'MASTER TLPG',
                    ]
                );

            $companySheet =
                $this->findSheet(
                    $spreadsheet,
                    [
                        'MASTER PERUSAHAAN',
                        'MASTER SPBE',
                    ]
                );

            $rotationSheet =
                $this->findSheet(
                    $spreadsheet,
                    [
                        'SETTING ROTASI',
                        'ROTASI SETTINGS',
                    ]
                );

            $finalSheet =
                $this->findSheet(
                    $spreadsheet,
                    [
                        'PC SET UTAMA',
                    ]
                );

            $p1Sheet =
                $this->findOptionalSheet(
                    $spreadsheet,
                    [
                        'KENDARAAN P1',
                    ]
                );

            $terminals =
                $this->readTerminals(
                    $terminalSheet
                );

            $companies =
                $this->readCompanies(
                    $companySheet
                );

            $rotation =
                $this->readRotation(
                    $rotationSheet
                );

            $final =
                $this->readFinalGrouping(
                    $finalSheet
                );

            $p1 =
                $this->readP1Vehicles(
                    $p1Sheet,
                    $final
                );

            $comparison =
                $this->compareGrouping(
                    $rotation,
                    $final
                );

            return [
                'summary' => [
                    'terminal_rows' =>
                        count(
                            $terminals['items']
                        ),

                    'terminal_invalid' =>
                        count(
                            $terminals['invalid']
                        ),

                    'company_rows' =>
                        count(
                            $companies['items']
                        ),

                    'company_invalid' =>
                        count(
                            $companies['invalid']
                        ),

                    'rotation_rows' =>
                        count(
                            $rotation['items']
                        ),

                    'final_rows' =>
                        count(
                            $final['items']
                        ),

                    'unique_rotation_vehicles' =>
                        count(
                            $rotation['by_plate']
                        ),

                    'unique_final_vehicles' =>
                        count(
                            $final['by_plate']
                        ),

                    'matched' =>
                        count(
                            $comparison['matched']
                        ),

                    'pc_mismatch' =>
                        count(
                            $comparison['pc_mismatch']
                        ),

                    'terminal_mismatch' =>
                        count(
                            $comparison[
                                'terminal_mismatch'
                            ]
                        ),

                    'company_mismatch' =>
                        count(
                            $comparison[
                                'company_mismatch'
                            ]
                        ),

                    'missing_in_final' =>
                        count(
                            $comparison[
                                'missing_in_final'
                            ]
                        ),

                    'missing_in_rotation' =>
                        count(
                            $comparison[
                                'missing_in_rotation'
                            ]
                        ),

                    'rotation_duplicates' =>
                        count(
                            $rotation['duplicates']
                        ),

                    'final_duplicates' =>
                        count(
                            $final['duplicates']
                        ),

                    'placeholder_companies' =>
                        count(
                            $rotation[
                                'placeholder_companies'
                            ]
                        ),

                    'p1_sheet_found' =>
                        (bool) (
                            $p1['sheet_found']
                            ?? false
                        ),

                    'p1_source_rows' =>
                        (int) (
                            $p1['source_row_count']
                            ?? 0
                        ),

                    'p1_vehicle_count' =>
                        count(
                            $p1['by_plate']
                        ),

                    'p2_vehicle_count' =>
                        max(
                            0,
                            count(
                                $final['by_plate']
                            )
                            -
                            count(
                                $p1['by_plate']
                            )
                        ),

                    'p1_duplicates_resolved' =>
                        count(
                            $p1['duplicates']
                        ),

                    'p1_invalid' =>
                        count(
                            $p1['invalid']
                        ),

                    'p1_missing_in_final' =>
                        count(
                            $p1['missing_in_final']
                        ),

                    'p1_conflicts_resolved' =>
                        count(
                            $p1['resolved_conflicts']
                        ),

                    'ready_for_import' =>
                        $this->isReadyForImport(
                            $terminals,
                            $companies,
                            $rotation,
                            $final,
                            $comparison,
                            $p1
                        ),

                    'official_vehicle_count' =>
                        count(
                            $final['by_plate']
                        ),

                    'rotation_only_ignored' =>
                        count(
                            $comparison[
                                'missing_in_final'
                            ]
                        ),

                    'final_without_rotation' =>
                        count(
                            $comparison[
                                'missing_in_rotation'
                            ]
                        ),
                ],

                'terminals' =>
                    $terminals,

                'companies' =>
                    $companies,

                'rotation' =>
                    $rotation,

                'final' =>
                    $final,

                'p1' =>
                    $p1,

                'comparison' =>
                    $comparison,

                'mapping' => [
                    'MASTER TLPG' => [
                        'start_row' => 3,
                        'A' => 'Nama TLPG',
                        'B' => 'Koordinat',
                    ],

                    'MASTER PERUSAHAAN' => [
                        'start_row' => 3,
                        'A' =>
                            'Nama perusahaan/SPBE',

                        'B' =>
                            'Koordinat perusahaan',
                    ],

                    'SETTING ROTASI' => [
                        'start_row' => 2,
                        'A' => 'Nopol',
                        'B' => 'TLPG',
                        'C' => 'PC awal',
                        'D' => 'PC akhir/target',
                        'E' => 'Perusahaan',
                    ],

                    'PC SET UTAMA' => [
                        'start_row' => 2,
                        'A' => 'PC',
                        'B' => 'Nopol',
                        'C' => 'TLPG',
                        'D' => 'Perusahaan',
                        'E' => 'PC final',
                    ],

                    'KENDARAAN P1' => [
                        'start_row' => 2,
                        'A' => 'Nopol',
                        'B' => 'TLPG referensi',
                        'C' => 'Operator/Pemilik P1',
                    ],
                ],
            ];
        } finally {
            $spreadsheet
                ->disconnectWorksheets();

            unset($spreadsheet);
        }
    }

    private function findSheet(
        Spreadsheet $spreadsheet,
        array $possibleNames
    ): Worksheet {
        foreach (
            $spreadsheet->getWorksheetIterator()
            as $worksheet
        ) {
            $currentName =
                $this->normalizeText(
                    $worksheet->getTitle()
                );

            foreach (
                $possibleNames
                as $possibleName
            ) {
                if (
                    $currentName
                    ===
                    $this->normalizeText(
                        $possibleName
                    )
                ) {
                    return $worksheet;
                }
            }
        }

        throw new RuntimeException(
            'Sheet tidak ditemukan: '
            .
            implode(
                ' / ',
                $possibleNames
            )
        );
    }

    private function findOptionalSheet(
        Spreadsheet $spreadsheet,
        array $possibleNames
    ): ?Worksheet {
        foreach (
            $spreadsheet->getWorksheetIterator()
            as $worksheet
        ) {
            $currentName =
                $this->normalizeText(
                    $worksheet->getTitle()
                );

            foreach (
                $possibleNames
                as $possibleName
            ) {
                if (
                    $currentName
                    ===
                    $this->normalizeText(
                        $possibleName
                    )
                ) {
                    return $worksheet;
                }
            }
        }

        return null;
    }

    private function readTerminals(
        Worksheet $sheet
    ): array {
        $items = [];

        $invalid = [];

        $duplicates = [];

        $seen = [];

        $highestRow =
            $sheet->getHighestDataRow();

        for (
            $row = 3;
            $row <= $highestRow;
            $row++
        ) {
            $name =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'A' . $row
                    )
                );

            $coordinateText =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'B' . $row
                    )
                );

            if (
                $name === ''
                &&
                $coordinateText === ''
            ) {
                continue;
            }

            if ($name === '') {
                $invalid[] = [
                    'row' => $row,
                    'reason' =>
                        'Nama TLPG kosong.',
                ];

                continue;
            }

            try {
                $coordinates =
                    CoordinateParser::parse(
                        $coordinateText
                    );
            } catch (Throwable $e) {
                $invalid[] = [
                    'row' => $row,
                    'name' => $name,
                    'coordinate' =>
                        $coordinateText,

                    'reason' =>
                        $e->getMessage(),
                ];

                continue;
            }

            $normalized =
                $this->normalizeText(
                    $name
                );

            if (isset($seen[$normalized])) {
                $duplicates[] = [
                    'name' => $name,
                    'first_row' =>
                        $seen[$normalized],

                    'duplicate_row' =>
                        $row,
                ];

                continue;
            }

            $seen[$normalized] = $row;

            $items[] = [
                'row' => $row,
                'name' => $name,
                'normalized_name' =>
                    $normalized,

                'coordinate_text' =>
                    $coordinateText,

                'latitude' =>
                    $coordinates[
                        'latitude'
                    ],

                'longitude' =>
                    $coordinates[
                        'longitude'
                    ],
            ];
        }

        return [
            'items' => $items,
            'invalid' => $invalid,
            'duplicates' => $duplicates,
        ];
    }

    private function readCompanies(
        Worksheet $sheet
    ): array {
        $items = [];

        $invalid = [];

        $duplicates = [];

        $seen = [];

        $highestRow =
            $sheet->getHighestDataRow();

        for (
            $row = 3;
            $row <= $highestRow;
            $row++
        ) {
            $name =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'A' . $row
                    )
                );

            $coordinateText =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'B' . $row
                    )
                );

            if (
                $name === ''
                &&
                $coordinateText === ''
            ) {
                continue;
            }

            if ($name === '') {
                $invalid[] = [
                    'row' => $row,
                    'reason' =>
                        'Nama perusahaan kosong.',
                ];

                continue;
            }

            if (
                $this->isPlaceholderCompany(
                    $name
                )
            ) {
                $invalid[] = [
                    'row' => $row,
                    'name' => $name,
                    'reason' =>
                        'Nama perusahaan merupakan placeholder.',
                ];

                continue;
            }

            try {
                $coordinates =
                    CoordinateParser::parse(
                        $coordinateText
                    );
            } catch (Throwable $e) {
                $invalid[] = [
                    'row' => $row,
                    'name' => $name,
                    'coordinate' =>
                        $coordinateText,

                    'reason' =>
                        $e->getMessage(),
                ];

                continue;
            }

            $normalized =
                $this->normalizeText(
                    $name
                );

            if (isset($seen[$normalized])) {
                $duplicates[] = [
                    'name' => $name,
                    'first_row' =>
                        $seen[$normalized],

                    'duplicate_row' =>
                        $row,
                ];

                continue;
            }

            $seen[$normalized] = $row;

            $items[] = [
                'row' => $row,
                'name' => $name,
                'normalized_name' =>
                    $normalized,

                'coordinate_text' =>
                    $coordinateText,

                'latitude' =>
                    $coordinates[
                        'latitude'
                    ],

                'longitude' =>
                    $coordinates[
                        'longitude'
                    ],
            ];
        }

        return [
            'items' => $items,
            'invalid' => $invalid,
            'duplicates' => $duplicates,
        ];
    }

    private function readRotation(
        Worksheet $sheet
    ): array {
        $items = [];

        $byPlate = [];

        $duplicates = [];

        $invalid = [];

        $placeholderCompanies = [];

        $highestRow =
            $sheet->getHighestDataRow();

        for (
            $row = 2;
            $row <= $highestRow;
            $row++
        ) {
            $plate =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'A' . $row
                    )
                );

            $terminal =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'B' . $row
                    )
                );

            $pcInitial =
                $this->parsePc(
                    $this->cellValue(
                        $sheet,
                        'C' . $row
                    )
                );

            $pcTarget =
                $this->parsePc(
                    $this->cellValue(
                        $sheet,
                        'D' . $row
                    )
                );

            $company =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'E' . $row
                    )
                );

            if (
                $plate === ''
                &&
                $terminal === ''
                &&
                $company === ''
            ) {
                continue;
            }

            if ($plate === '') {
                $invalid[] = [
                    'row' => $row,
                    'reason' =>
                        'Nopol pada SETTING ROTASI kosong.',
                ];

                continue;
            }

            $normalizedPlate =
                FleetVehicle::normalizePlateNumber(
                    $plate
                );

            if ($normalizedPlate === '') {
                $invalid[] = [
                    'row' => $row,
                    'plate_number' => $plate,
                    'reason' =>
                        'Format nopol tidak valid.',
                ];

                continue;
            }

            if (
                isset(
                    $byPlate[
                        $normalizedPlate
                    ]
                )
            ) {
                $duplicates[] = [
                    'plate_number' => $plate,
                    'first_row' =>
                        $byPlate[
                            $normalizedPlate
                        ]['row'],

                    'duplicate_row' =>
                        $row,
                ];
            }

            $companyPlaceholder =
                $this->isPlaceholderCompany(
                    $company
                );

            if ($companyPlaceholder) {
                $placeholderCompanies[] = [
                    'row' => $row,
                    'plate_number' => $plate,
                    'company' => $company,
                ];
            }

            $item = [
                'row' => $row,
                'plate_number' =>
                    FleetVehicle::formatPlateNumber(
                        $plate
                    ),

                'normalized_plate_number' =>
                    $normalizedPlate,

                'terminal' => $terminal,

                'normalized_terminal' =>
                    $this->normalizeText(
                        $terminal
                    ),

                'company' =>
                    $companyPlaceholder
                        ? null
                        : $company,

                'normalized_company' =>
                    $companyPlaceholder
                        ? null
                        : $this->normalizeText(
                            $company
                        ),

                'pc_initial' =>
                    $pcInitial,

                'pc_target' =>
                    $pcTarget,

                'company_placeholder' =>
                    $companyPlaceholder,
            ];

            $items[] = $item;

            $byPlate[
                $normalizedPlate
            ] = $item;
        }

        return [
            'items' =>
                array_slice(
                    $items,
                    0,
                    self::MAX_DETAIL_ROWS
                ),

            'by_plate' => $byPlate,
            'duplicates' => $duplicates,
            'invalid' => $invalid,

            'placeholder_companies' =>
                $placeholderCompanies,
        ];
    }

    private function readFinalGrouping(
        Worksheet $sheet
    ): array {
        $items = [];

        $byPlate = [];

        $duplicates = [];

        $invalid = [];

        $highestRow =
            $sheet->getHighestDataRow();

        for (
            $row = 2;
            $row <= $highestRow;
            $row++
        ) {
            $pcColumn =
                $this->parsePc(
                    $this->cellValue(
                        $sheet,
                        'A' . $row
                    )
                );

            $plate =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'B' . $row
                    )
                );

            $terminal =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'C' . $row
                    )
                );

            $company =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'D' . $row
                    )
                );

            $pcFinal =
                $this->parsePc(
                    $this->cellValue(
                        $sheet,
                        'E' . $row
                    )
                );

            if (
                $plate === ''
                &&
                $terminal === ''
                &&
                $company === ''
            ) {
                continue;
            }

            if ($plate === '') {
                $invalid[] = [
                    'row' => $row,
                    'reason' =>
                        'Nopol pada PC SET UTAMA kosong.',
                ];

                continue;
            }

            $normalizedPlate =
                FleetVehicle::normalizePlateNumber(
                    $plate
                );

            if (
                isset(
                    $byPlate[
                        $normalizedPlate
                    ]
                )
            ) {
                $duplicates[] = [
                    'plate_number' => $plate,
                    'first_row' =>
                        $byPlate[
                            $normalizedPlate
                        ]['row'],

                    'duplicate_row' =>
                        $row,
                ];
            }

            $companyPlaceholder =
                $this->isPlaceholderCompany(
                    $company
                );

            $item = [
                'row' => $row,

                'plate_number' =>
                    FleetVehicle::formatPlateNumber(
                        $plate
                    ),

                'normalized_plate_number' =>
                    $normalizedPlate,

                'terminal' => $terminal,

                'normalized_terminal' =>
                    $this->normalizeText(
                        $terminal
                    ),

                'company' =>
                    $companyPlaceholder
                        ? null
                        : $company,

                'normalized_company' =>
                    $companyPlaceholder
                        ? null
                        : $this->normalizeText(
                            $company
                        ),

                'pc_column' =>
                    $pcColumn,

                'pc_final' =>
                    $pcFinal
                    ??
                    $pcColumn,

                'company_placeholder' =>
                    $companyPlaceholder,
            ];

            $items[] = $item;

            $byPlate[
                $normalizedPlate
            ] = $item;
        }

        return [
            'items' =>
                array_slice(
                    $items,
                    0,
                    self::MAX_DETAIL_ROWS
                ),

            'by_plate' => $byPlate,
            'duplicates' => $duplicates,
            'invalid' => $invalid,
        ];
    }

    private function readP1Vehicles(
        ?Worksheet $sheet,
        array $final
    ): array {
        if ($sheet === null) {
            return [
                'sheet_found' => false,
                'source_row_count' => 0,
                'items' => [],
                'by_plate' => [],
                'duplicates' => [],
                'invalid' => [],
                'missing_in_final' => [],
                'resolved_conflicts' => [],
            ];
        }

        $candidatesByPlate = [];
        $invalid = [];
        $sourceRowCount = 0;

        $highestRow =
            $sheet->getHighestDataRow();

        for (
            $row = 2;
            $row <= $highestRow;
            $row++
        ) {
            $plate =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'A' . $row
                    )
                );

            $terminal =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'B' . $row
                    )
                );

            $operatorName =
                $this->cleanText(
                    $this->cellValue(
                        $sheet,
                        'C' . $row
                    )
                );

            if (
                $plate === ''
                &&
                $terminal === ''
                &&
                $operatorName === ''
            ) {
                continue;
            }

            $sourceRowCount++;

            if ($plate === '') {
                $invalid[] = [
                    'row' => $row,
                    'reason' =>
                        'Nopol pada KENDARAAN P1 kosong.',
                ];

                continue;
            }

            $normalizedPlate =
                FleetVehicle::normalizePlateNumber(
                    $plate
                );

            if ($normalizedPlate === '') {
                $invalid[] = [
                    'row' => $row,
                    'plate_number' => $plate,
                    'reason' =>
                        'Format nopol P1 tidak valid.',
                ];

                continue;
            }

            if ($operatorName === '') {
                $invalid[] = [
                    'row' => $row,
                    'plate_number' =>
                        FleetVehicle::formatPlateNumber(
                            $plate
                        ),

                    'reason' =>
                        'Operator/pemilik kendaraan P1 kosong.',
                ];

                continue;
            }

            $candidate = [
                'row' => $row,

                'plate_number' =>
                    FleetVehicle::formatPlateNumber(
                        $plate
                    ),

                'normalized_plate_number' =>
                    $normalizedPlate,

                'terminal' =>
                    $terminal,

                'normalized_terminal' =>
                    $this->normalizeText(
                        $terminal
                    ),

                'operator_name' =>
                    mb_strtoupper(
                        $operatorName,
                        'UTF-8'
                    ),

                'normalized_operator_name' =>
                    $this->normalizeText(
                        $operatorName
                    ),
            ];

            $candidatesByPlate[
                $normalizedPlate
            ][] = $candidate;
        }

        $items = [];
        $byPlate = [];
        $duplicates = [];
        $missingInFinal = [];
        $resolvedConflicts = [];

        foreach (
            $candidatesByPlate
            as $plateKey => $candidates
        ) {
            $finalItem =
                $final['by_plate'][
                    $plateKey
                ]
                ?? null;

            if ($finalItem === null) {
                $missingInFinal[] = [
                    'plate_number' =>
                        $candidates[0][
                            'plate_number'
                        ],

                    'source_rows' =>
                        array_values(
                            array_map(
                                static fn (array $item): int =>
                                    (int) $item['row'],
                                $candidates
                            )
                        ),

                    'reason' =>
                        'Nopol P1 tidak terdapat pada PC SET UTAMA.',
                ];

                continue;
            }

            $finalTerminal =
                $this->normalizeText(
                    (string) (
                        $finalItem['terminal']
                        ?? ''
                    )
                );

            $finalOperator =
                $this->normalizeText(
                    (string) (
                        $finalItem['company']
                        ?? ''
                    )
                );

            usort(
                $candidates,
                static function (
                    array $left,
                    array $right
                ) use (
                    $finalTerminal,
                    $finalOperator
                ): int {
                    $leftScore =
                        (
                            $finalTerminal !== ''
                            &&
                            $left[
                                'normalized_terminal'
                            ] === $finalTerminal
                                ? 2
                                : 0
                        )
                        +
                        (
                            $finalOperator !== ''
                            &&
                            $left[
                                'normalized_operator_name'
                            ] === $finalOperator
                                ? 4
                                : 0
                        );

                    $rightScore =
                        (
                            $finalTerminal !== ''
                            &&
                            $right[
                                'normalized_terminal'
                            ] === $finalTerminal
                                ? 2
                                : 0
                        )
                        +
                        (
                            $finalOperator !== ''
                            &&
                            $right[
                                'normalized_operator_name'
                            ] === $finalOperator
                                ? 4
                                : 0
                        );

                    if ($leftScore !== $rightScore) {
                        return $rightScore
                            <=>
                            $leftScore;
                    }

                    return (int) $right['row']
                        <=>
                        (int) $left['row'];
                }
            );

            $selected =
                $candidates[0];

            $canonicalTerminal =
                trim(
                    (string) (
                        $finalItem['terminal']
                        ?? ''
                    )
                );

            $canonicalOperator =
                trim(
                    (string) (
                        $finalItem['company']
                        ?? ''
                    )
                );

            if ($canonicalTerminal === '') {
                $canonicalTerminal =
                    $selected['terminal'];
            }

            if ($canonicalOperator === '') {
                $canonicalOperator =
                    $selected['operator_name'];
            }

            $canonicalOperator =
                mb_strtoupper(
                    $this->cleanText(
                        $canonicalOperator
                    ),
                    'UTF-8'
                );

            if (
                count($candidates) > 1
            ) {
                $duplicates[] = [
                    'plate_number' =>
                        $selected['plate_number'],

                    'source_rows' =>
                        array_values(
                            array_map(
                                static fn (array $item): int =>
                                    (int) $item['row'],
                                $candidates
                            )
                        ),

                    'selected_row' =>
                        (int) $selected['row'],

                    'selected_terminal' =>
                        $canonicalTerminal,

                    'selected_operator_name' =>
                        $canonicalOperator,

                    'reason' =>
                        'Duplikat diselesaikan menggunakan PC SET UTAMA sebagai referensi resmi.',
                ];
            }

            if (
                $selected[
                    'normalized_terminal'
                ] !==
                $this->normalizeText(
                    $canonicalTerminal
                )
                ||
                $selected[
                    'normalized_operator_name'
                ] !==
                $this->normalizeText(
                    $canonicalOperator
                )
            ) {
                $resolvedConflicts[] = [
                    'plate_number' =>
                        $selected['plate_number'],

                    'selected_row' =>
                        (int) $selected['row'],

                    'source_terminal' =>
                        $selected['terminal'],

                    'official_terminal' =>
                        $canonicalTerminal,

                    'source_operator_name' =>
                        $selected[
                            'operator_name'
                        ],

                    'official_operator_name' =>
                        $canonicalOperator,
                ];
            }

            $item = [
                ...$selected,

                'terminal' =>
                    $canonicalTerminal,

                'normalized_terminal' =>
                    $this->normalizeText(
                        $canonicalTerminal
                    ),

                'operator_name' =>
                    $canonicalOperator,

                'normalized_operator_name' =>
                    $this->normalizeText(
                        $canonicalOperator
                    ),

                'source_rows' =>
                    array_values(
                        array_map(
                            static fn (array $candidate): int =>
                                (int) $candidate['row'],
                            $candidates
                        )
                    ),

                'official_final_row' =>
                    $finalItem['row']
                    ?? null,
            ];

            $items[] = $item;

            $byPlate[
                $plateKey
            ] = $item;
        }

        return [
            'sheet_found' => true,
            'source_row_count' =>
                $sourceRowCount,

            'items' =>
                array_slice(
                    $items,
                    0,
                    self::MAX_DETAIL_ROWS
                ),

            'by_plate' =>
                $byPlate,

            'duplicates' =>
                $duplicates,

            'invalid' =>
                $invalid,

            'missing_in_final' =>
                $missingInFinal,

            'resolved_conflicts' =>
                $resolvedConflicts,
        ];
    }

    private function compareGrouping(
        array $rotation,
        array $final
    ): array {
        $matched = [];

        $pcMismatch = [];

        $terminalMismatch = [];

        $companyMismatch = [];

        $missingInFinal = [];

        $missingInRotation = [];

        foreach (
            $rotation['by_plate']
            as $plateKey => $rotationItem
        ) {
            $finalItem =
                $final['by_plate'][
                    $plateKey
                ]
                ?? null;

            if ($finalItem === null) {
                $missingInFinal[] =
                    $rotationItem;

                continue;
            }

            $hasMismatch = false;

            if (
                $rotationItem['pc_target']
                !== null
                &&
                $finalItem['pc_final']
                !== null
                &&
                $rotationItem['pc_target']
                !==
                $finalItem['pc_final']
            ) {
                $hasMismatch = true;

                $pcMismatch[] = [
                'plate_number' =>
                    $rotationItem[
                        'plate_number'
                    ],

                'rotation_row' =>
                    $rotationItem['row'],

                'final_row' =>
                    $finalItem['row'],

                'pc_initial' =>
                    $rotationItem[
                        'pc_initial'
                    ],

                'pc_target' =>
                    $rotationItem[
                        'pc_target'
                    ],

                'pc_final' =>
                    $finalItem[
                        'pc_final'
                    ],
            ];
            }

            if (
                $rotationItem[
                    'normalized_terminal'
                ] !== ''
                &&
                $finalItem[
                    'normalized_terminal'
                ] !== ''
                &&
                $rotationItem[
                    'normalized_terminal'
                ]
                !==
                $finalItem[
                    'normalized_terminal'
                ]
            ) {
                $hasMismatch = true;

                $terminalMismatch[] = [
                    'plate_number' =>
                        $rotationItem[
                            'plate_number'
                        ],

                    'rotation_terminal' =>
                        $rotationItem[
                            'terminal'
                        ],

                    'final_terminal' =>
                        $finalItem[
                            'terminal'
                        ],
                ];
            }

            if (
                $rotationItem[
                    'normalized_company'
                ] !== null
                &&
                $finalItem[
                    'normalized_company'
                ] !== null
                &&
                $rotationItem[
                    'normalized_company'
                ]
                !==
                $finalItem[
                    'normalized_company'
                ]
            ) {
                $hasMismatch = true;

                $companyMismatch[] = [
                    'plate_number' =>
                        $rotationItem[
                            'plate_number'
                        ],

                    'rotation_company' =>
                        $rotationItem[
                            'company'
                        ],

                    'final_company' =>
                        $finalItem[
                            'company'
                        ],
                ];
            }

            if (!$hasMismatch) {
                $matched[] = [
                    'plate_number' =>
                        $rotationItem[
                            'plate_number'
                        ],

                    'pc_initial' =>
                        $rotationItem[
                            'pc_initial'
                        ],

                    'pc_target' =>
                        $rotationItem[
                            'pc_target'
                        ],

                    'pc_final' =>
                        $finalItem[
                            'pc_final'
                        ],

                    'terminal' =>
                        $finalItem[
                            'terminal'
                        ],

                    'company' =>
                        $finalItem[
                            'company'
                        ],
                ];
            }
        }

        foreach (
            $final['by_plate']
            as $plateKey => $finalItem
        ) {
            if (
                !isset(
                    $rotation[
                        'by_plate'
                    ][$plateKey]
                )
            ) {
                $missingInRotation[] =
                    $finalItem;
            }
        }

        return [
            'matched' =>
                array_slice(
                    $matched,
                    0,
                    self::MAX_DETAIL_ROWS
                ),

            'pc_mismatch' =>
                $pcMismatch,

            'terminal_mismatch' =>
                $terminalMismatch,

            'company_mismatch' =>
                $companyMismatch,

            'missing_in_final' =>
                $missingInFinal,

            'missing_in_rotation' =>
                $missingInRotation,
        ];
    }

    private function cellValue(
        Worksheet $sheet,
        string $coordinate
    ): string {
        $cell =
            $sheet->getCell(
                $coordinate
            );

        $raw =
            $cell->getValue();

        $isFormula =
            $cell->getDataType()
                ===
                DataType::TYPE_FORMULA
            ||
            (
                is_string($raw)
                &&
                str_starts_with(
                    $raw,
                    '='
                )
            );

        if ($isFormula) {
            /*
             * Nilai cache hasil terakhir dari Google Sheets
             * lebih aman untuk formula yang memakai Apps Script
             * atau referensi internal.
             */

            if (
                method_exists(
                    $cell,
                    'getOldCalculatedValue'
                )
            ) {
                $oldValue =
                    $cell
                        ->getOldCalculatedValue();

                if (
                    $oldValue !== null
                    &&
                    $oldValue !== ''
                ) {
                    return $this->stringValue(
                        $oldValue
                    );
                }
            }

            try {
                return $this->stringValue(
                    $cell->getCalculatedValue()
                );
            } catch (Throwable) {
                return '';
            }
        }

        try {
            return $this->stringValue(
                $cell->getFormattedValue()
            );
        } catch (Throwable) {
            return $this->stringValue(
                $raw
            );
        }
    }

    private function parsePc(
        mixed $value
    ): ?int {
        $value =
            $this->cleanText(
                $this->stringValue(
                    $value
                )
            );

        if ($value === '') {
            return null;
        }

        if (
            !preg_match(
                '/(\d{1,2})/',
                $value,
                $matches
            )
        ) {
            return null;
        }

        $pc =
            (int) $matches[1];

        $operatorCount =
            (int) config(
                'master-fleet.operator_count',
                12
            );

        if (
            $pc < 1
            ||
            $pc > $operatorCount
        ) {
            return null;
        }

        return $pc;
    }

    private function isPlaceholderCompany(
        ?string $value
    ): bool {
        $normalized =
            $this->normalizeText(
                (string) $value
            );

        return in_array(
            $normalized,
            [
                '',
                '-',
                'N/A',
                'NA',
                'BELUM ADA',
                'DATA COMPANY BELUM ADA',
                'COMPANY BELUM ADA',
                'PERUSAHAAN BELUM ADA',
                'TIDAK ADA',
            ],
            true
        );
    }

    private function normalizeText(
        string $value
    ): string {
        return mb_strtoupper(
            $this->cleanText(
                $value
            ),
            'UTF-8'
        );
    }

    private function cleanText(
        string $value
    ): string {
        return trim(
            (string) preg_replace(
                '/\s+/u',
                ' ',
                $value
            )
        );
    }

    private function stringValue(
        mixed $value
    ): string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return '';
        }

        if (is_bool($value)) {
            return $value
                ? '1'
                : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    private function isReadyForImport(
        array $terminals,
        array $companies,
        array $rotation,
        array $final,
        array $comparison,
        array $p1
    ): bool {
        return count(
            $terminals['invalid']
        ) === 0
            &&
            count(
                $terminals['duplicates']
            ) === 0
            &&
            count(
                $companies['duplicates']
            ) === 0
            &&
            count(
                $final['duplicates']
            ) === 0
            &&
            count(
                $final['invalid']
            ) === 0
            &&
            count(
                $p1['invalid']
            ) === 0
            &&
            count(
                $p1['missing_in_final']
            ) === 0;
    }
}