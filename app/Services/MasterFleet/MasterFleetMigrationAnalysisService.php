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

                    'ready_for_import' =>
                        $this->isReadyForImport(
                            $terminals,
                            $companies,
                            $rotation,
                            $final,
                            $comparison
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
    array $comparison
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
            ) === 0;
    }
}