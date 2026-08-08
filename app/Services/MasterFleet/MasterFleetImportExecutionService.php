<?php

namespace App\Services\MasterFleet;

use App\Models\FleetCompany;
use App\Models\FleetDistanceProfile;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetImportBatch;
use App\Models\FleetTerminal;
use App\Models\FleetVehicle;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MasterFleetImportExecutionService
{
    /**
     * Menjalankan import resmi Master Fleet.
     *
     * PC SET UTAMA menjadi sumber resmi kendaraan aktif.
     * SETTING ROTASI hanya melengkapi PC awal dan PC target.
     */
    public function execute(
        FleetImportBatch $batch,
        int $userId,
        string $groupingName,
        string $effectiveDate,
        bool $syncSnapshot = true
    ): array {
        return DB::transaction(
            function () use (
                $batch,
                $userId,
                $groupingName,
                $effectiveDate,
                $syncSnapshot
            ): array {
                /*
                |--------------------------------------------------------------------------
                | Kunci batch agar tidak dapat diimpor bersamaan
                |--------------------------------------------------------------------------
                */

                $lockedBatch =
                    FleetImportBatch::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $batch->getKey()
                        );

                if (
                    $lockedBatch->status === 'imported'
                    ||
                    $lockedBatch->imported_at !== null
                ) {
                    throw new RuntimeException(
                        'Batch ini sudah pernah diimpor.'
                    );
                }

                $analysis =
                    $lockedBatch->analysis_json;

                if (
                    !is_array($analysis)
                    ||
                    empty($analysis)
                ) {
                    throw new RuntimeException(
                        'Data analisis batch tidak tersedia.'
                    );
                }

                $terminalItems =
                    data_get(
                        $analysis,
                        'terminals.items',
                        []
                    );

                $companyItems =
                    data_get(
                        $analysis,
                        'companies.items',
                        []
                    );

                $rotationByPlate =
                    data_get(
                        $analysis,
                        'rotation.by_plate',
                        []
                    );

                /*
                 * Sumber resmi 333 kendaraan.
                 */
                $finalByPlate =
                    data_get(
                        $analysis,
                        'final.by_plate',
                        []
                    );

                $p1ByPlate =
                    data_get(
                        $analysis,
                        'p1.by_plate',
                        []
                    );

                $p1Invalid =
                    data_get(
                        $analysis,
                        'p1.invalid',
                        []
                    );

                $p1MissingInFinal =
                    data_get(
                        $analysis,
                        'p1.missing_in_final',
                        []
                    );

                $finalDuplicates =
                    data_get(
                        $analysis,
                        'final.duplicates',
                        []
                    );

                $finalInvalid =
                    data_get(
                        $analysis,
                        'final.invalid',
                        []
                    );

                if (
                    !is_array($terminalItems)
                    ||
                    count($terminalItems) === 0
                ) {
                    throw new RuntimeException(
                        'Data MASTER TLPG tidak ditemukan.'
                    );
                }

                if (
                    !is_array($companyItems)
                    ||
                    count($companyItems) === 0
                ) {
                    throw new RuntimeException(
                        'Data MASTER PERUSAHAAN tidak ditemukan.'
                    );
                }

                if (
                    !is_array($finalByPlate)
                    ||
                    count($finalByPlate) === 0
                ) {
                    throw new RuntimeException(
                        'Data PC SET UTAMA tidak ditemukan.'
                    );
                }

                if (
                    count($finalDuplicates) > 0
                ) {
                    throw new RuntimeException(
                        'PC SET UTAMA masih memiliki nopol duplikat.'
                    );
                }

                if (
                    count($finalInvalid) > 0
                ) {
                    throw new RuntimeException(
                        'PC SET UTAMA masih memiliki data invalid.'
                    );
                }

                if (
                    !is_array($p1ByPlate)
                    ||
                    !is_array($p1Invalid)
                    ||
                    !is_array($p1MissingInFinal)
                ) {
                    throw new RuntimeException(
                        'Data analisis kendaraan P1 tidak valid.'
                    );
                }

                if (
                    count($p1Invalid) > 0
                ) {
                    throw new RuntimeException(
                        'Sheet KENDARAAN P1 masih memiliki data invalid.'
                    );
                }

                if (
                    count($p1MissingInFinal) > 0
                ) {
                    throw new RuntimeException(
                        'Terdapat kendaraan P1 yang tidak ada pada PC SET UTAMA.'
                    );
                }

                $officialVehicleCount =
                    count($finalByPlate);

                $officialP1Count =
                    count($p1ByPlate);

                $officialP2Count =
                    $officialVehicleCount
                    -
                    $officialP1Count;

                if (
                    $officialP2Count < 0
                ) {
                    throw new RuntimeException(
                        'Jumlah kendaraan P1 melebihi kendaraan resmi.'
                    );
                }

                $statistics = [
                    'official_vehicle_count' =>
                        $officialVehicleCount,

                    'p1_vehicle_count' =>
                        $officialP1Count,

                    'p2_vehicle_count' =>
                        $officialP2Count,

                    'p1_vehicles_imported' => 0,
                    'p2_vehicles_imported' => 0,

                    'terminals_created' => 0,
                    'terminals_updated' => 0,
                    'terminals_deactivated' => 0,

                    'companies_created' => 0,
                    'companies_updated' => 0,
                    'companies_deactivated' => 0,

                    'vehicles_created' => 0,
                    'vehicles_updated' => 0,
                    'vehicles_deactivated' => 0,

                    'assignments_created' => 0,
                    'distance_profiles_created' => 0,

                    'matched_rotation' => 0,
                    'pc_changed' => 0,
                    'final_only' => 0,

                    'company_placeholders' => 0,
                    'company_unresolved' => 0,
                    'company_multiple_terminals' => 0,

                    'rotation_only_ignored' =>
                        count(
                            data_get(
                                $analysis,
                                'comparison.missing_in_final',
                                []
                            )
                        ),

                    'pc_counts' => [],
                ];

                /*
                |--------------------------------------------------------------------------
                | Import MASTER TLPG
                |--------------------------------------------------------------------------
                */

                $terminalExactMap = [];

                $terminalLooseMap = [];

                $importedTerminalNames = [];

                foreach (
                    $terminalItems
                    as $terminalItem
                ) {
                    $name =
                        trim(
                            (string) (
                                $terminalItem['name']
                                ?? ''
                            )
                        );

                    if ($name === '') {
                        continue;
                    }

                    $normalizedName =
                        FleetTerminal::normalizeName(
                            $name
                        );

                    $terminal =
                        FleetTerminal::query()
                            ->where(
                                'normalized_name',
                                $normalizedName
                            )
                            ->first();

                    $created =
                        $terminal === null;

                    if ($terminal === null) {
                        $terminal =
                            new FleetTerminal();
                    }

                    $terminal->fill([
                        'name' => $name,

                        'latitude' =>
                            $terminalItem['latitude']
                            ?? null,

                        'longitude' =>
                            $terminalItem['longitude']
                            ?? null,

                        'is_active' => true,
                    ]);

                    $terminal->save();

                    if ($created) {
                        $statistics[
                            'terminals_created'
                        ]++;
                    } else {
                        $statistics[
                            'terminals_updated'
                        ]++;
                    }

                    $terminalExactMap[
                        $terminal->normalized_name
                    ] = $terminal;

                    $looseKey =
                        $this->terminalMatchKey(
                            $terminal->name
                        );

                    if ($looseKey !== '') {
                        $terminalLooseMap[
                            $looseKey
                        ][] = $terminal;
                    }

                    $importedTerminalNames[] =
                        $terminal->normalized_name;
                }

                /*
                 * Snapshot resmi:
                 * TLPG yang tidak ada pada workbook dinonaktifkan,
                 * bukan dihapus.
                 */
                if ($syncSnapshot) {
                    $statistics[
                        'terminals_deactivated'
                    ] =
                        FleetTerminal::query()
                            ->whereNotIn(
                                'normalized_name',
                                $importedTerminalNames
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->update([
                                'is_active' => false,
                            ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Import MASTER PERUSAHAAN
                |--------------------------------------------------------------------------
                */

                $companyExactMap = [];

                $companyLooseMap = [];

                $importedCompanyNames = [];

                foreach (
                    $companyItems
                    as $companyItem
                ) {
                    $name =
                        trim(
                            (string) (
                                $companyItem['name']
                                ?? ''
                            )
                        );

                    if ($name === '') {
                        continue;
                    }

                    $normalizedName =
                        FleetCompany::normalizeName(
                            $name
                        );

                    $company =
                        FleetCompany::query()
                            ->where(
                                'normalized_name',
                                $normalizedName
                            )
                            ->first();

                    $created =
                        $company === null;

                    if ($company === null) {
                        $company =
                            new FleetCompany();
                    }

                    $company->fill([
                        'name' => $name,

                        'latitude' =>
                            $companyItem['latitude']
                            ?? null,

                        'longitude' =>
                            $companyItem['longitude']
                            ?? null,

                        'is_active' => true,
                    ]);

                    $company->save();

                    if ($created) {
                        $statistics[
                            'companies_created'
                        ]++;
                    } else {
                        $statistics[
                            'companies_updated'
                        ]++;
                    }

                    $companyExactMap[
                        $company->normalized_name
                    ] = $company;

                    $looseKey =
                        $this->companyMatchKey(
                            $company->name
                        );

                    if ($looseKey !== '') {
                        $companyLooseMap[
                            $looseKey
                        ][] = $company;
                    }

                    $importedCompanyNames[] =
                        $company->normalized_name;
                }

                if ($syncSnapshot) {
                    $statistics[
                        'companies_deactivated'
                    ] =
                        FleetCompany::query()
                            ->whereNotIn(
                                'normalized_name',
                                $importedCompanyNames
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->update([
                                'is_active' => false,
                            ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Buat periode grouping baru
                |--------------------------------------------------------------------------
                */

                /*
                 * PC SET UTAMA dari file baru menjadi grouping aktif.
                 * Periode published sebelumnya tetap disimpan sebagai histori.
                 */
                FleetGroupingPeriod::query()
                    ->where(
                        'status',
                        'published'
                    )
                    ->update([
                        'status' => 'archived',
                    ]);

                $groupingPeriod =
                    FleetGroupingPeriod::query()
                        ->create([
                            'import_batch_id' =>
                                $lockedBatch->id,

                            'name' =>
                                trim($groupingName),

                            'effective_date' =>
                                $effectiveDate,

                            'status' =>
                                'published',

                            'source_file_name' =>
                                $lockedBatch
                                    ->original_name,

                            'created_by' =>
                                $userId,

                            'published_by' =>
                                $userId,

                            'published_at' =>
                                now(),

                            'notes' =>
                                'Grouping resmi dari PC SET UTAMA.',
                        ]);

                /*
                |--------------------------------------------------------------------------
                | Import 333 kendaraan resmi dari PC SET UTAMA
                |--------------------------------------------------------------------------
                */

                $officialPlateNumbers = [];

                $companyTerminalPairs = [];

                $companyTerminalCounts = [];

                $unresolvedCompanies = [];

                $unresolvedTerminals = [];

                foreach (
                    $finalByPlate
                    as $plateKey => $finalItem
                ) {
                    $normalizedPlateNumber =
                        FleetVehicle::normalizePlateNumber(
                            (string) $plateKey
                        );

                    if ($normalizedPlateNumber === '') {
                        throw new RuntimeException(
                            'Ditemukan nopol kosong pada PC SET UTAMA.'
                        );
                    }

                    $officialPlateNumbers[] =
                        $normalizedPlateNumber;

                    $plateNumber =
                        FleetVehicle::formatPlateNumber(
                            (string) (
                                $finalItem[
                                    'plate_number'
                                ]
                                ?? $plateKey
                            )
                        );

                    $p1Item =
                        $p1ByPlate[
                            $normalizedPlateNumber
                        ]
                        ??
                        $p1ByPlate[
                            $plateKey
                        ]
                        ??
                        null;

                    $isP1 =
                        is_array(
                            $p1Item
                        );

                    $operationalType =
                        $isP1
                            ? FleetVehicle::TYPE_P1
                            : FleetVehicle::TYPE_P2;

                    $operatorName = null;

                    if ($isP1) {
                        $operatorName =
                            mb_strtoupper(
                                trim(
                                    (string) (
                                        $p1Item[
                                            'operator_name'
                                        ]
                                        ?? ''
                                    )
                                ),
                                'UTF-8'
                            );

                        if ($operatorName === '') {
                            throw new RuntimeException(
                                'Operator kendaraan P1 kosong untuk nopol '
                                .
                                $plateNumber
                                .
                                '.'
                            );
                        }
                    }

                    /*
                     * TLPG wajib berhasil ditemukan.
                     */
                    $terminalName =
                        trim(
                            (string) (
                                $finalItem['terminal']
                                ?? ''
                            )
                        );

                    $terminal =
                        $this->resolveTerminal(
                            $terminalName,
                            $terminalExactMap,
                            $terminalLooseMap
                        );

                    if ($terminal === null) {
                        $unresolvedTerminals[
                            $terminalName
                        ] = true;

                        continue;
                    }

                    /*
                     * Kolom perusahaan pada kendaraan P1 berisi
                     * operator/pemilik, bukan SPBE tujuan.
                     */
                    $companyName =
                        trim(
                            (string) (
                                $finalItem['company']
                                ?? ''
                            )
                        );

                    $companyPlaceholder =
                        !$isP1
                        &&
                        (bool) (
                            $finalItem[
                                'company_placeholder'
                            ]
                            ?? false
                        );

                    $company = null;

                    if (
                        !$isP1
                        &&
                        !$companyPlaceholder
                        &&
                        $companyName !== ''
                    ) {
                        $company =
                            $this->resolveCompany(
                                $companyName,
                                $companyExactMap,
                                $companyLooseMap
                            );
                    }

                    if (
                        !$isP1
                        &&
                        $companyPlaceholder
                    ) {
                        $statistics[
                            'company_placeholders'
                        ]++;
                    } elseif (
                        !$isP1
                        &&
                        $companyName !== ''
                        &&
                        $company === null
                    ) {
                        $unresolvedCompanies[
                            $companyName
                        ] = true;
                    }

                    /*
                     * Cari data PC awal/target dari SETTING ROTASI.
                     */
                    $rotationItem =
                        $rotationByPlate[
                            $normalizedPlateNumber
                        ]
                        ??
                        $rotationByPlate[
                            $plateKey
                        ]
                        ??
                        null;

                    $pcInitial =
                        $rotationItem[
                            'pc_initial'
                        ]
                        ?? null;

                    $pcTarget =
                        $rotationItem[
                            'pc_target'
                        ]
                        ?? null;

                    $pcFinal =
                        $finalItem[
                            'pc_final'
                        ]
                        ?? null;

                    if (
                        $pcFinal === null
                        ||
                        $pcFinal < 1
                        ||
                        $pcFinal > (
                            (int) config(
                                'master-fleet.operator_count',
                                12
                            )
                        )
                    ) {
                        throw new RuntimeException(
                            'PC final tidak valid untuk nopol '
                            .
                            $plateNumber
                            .
                            '.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Tambah atau perbarui kendaraan
                    |--------------------------------------------------------------------------
                    */

                    $vehicle =
                        FleetVehicle::query()
                            ->where(
                                'normalized_plate_number',
                                $normalizedPlateNumber
                            )
                            ->first();

                    $created =
                        $vehicle === null;

                    if ($vehicle === null) {
                        $vehicle =
                            new FleetVehicle();
                    }

                    $vehicle->plate_number =
                        $plateNumber;

                    $vehicle->operational_type =
                        $operationalType;

                    $vehicle->operator_name =
                        $operatorName;

                    if ($isP1) {
                        /*
                         * P1 tidak mempunyai SPBE tujuan tetap.
                         */
                        $vehicle->company_id =
                            null;
                    } else {
                        /*
                         * Data manual P2 yang telah diperbaiki tidak
                         * dihapus oleh placeholder atau nama perusahaan
                         * yang gagal cocok.
                         */
                        if ($company !== null) {
                            $vehicle->company_id =
                                $company->id;
                        } elseif ($created) {
                            $vehicle->company_id =
                                null;
                        }
                    }

                    if (
                        $created
                        ||
                        $vehicle->effective_from === null
                    ) {
                        $vehicle->effective_from =
                            $effectiveDate;
                    }

                    $vehicle->effective_until =
                        null;

                    $vehicle->is_active =
                        true;

                    $vehicle->save();

                    if ($created) {
                        $statistics[
                            'vehicles_created'
                        ]++;
                    } else {
                        $statistics[
                            'vehicles_updated'
                        ]++;
                    }

                    if ($isP1) {
                        $statistics[
                            'p1_vehicles_imported'
                        ]++;
                    } else {
                        $statistics[
                            'p2_vehicles_imported'
                        ]++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Tentukan status hasil analisis
                    |--------------------------------------------------------------------------
                    */

                    $validationStatus =
                        'matched';

                    $validationNotes = [];

                    if ($rotationItem === null) {
                        $validationStatus =
                            'final_only';

                        $validationNotes[] =
                            'Nopol hanya terdapat pada PC SET UTAMA.';

                        $statistics[
                            'final_only'
                        ]++;
                    } else {
                        $statistics[
                            'matched_rotation'
                        ]++;

                        if (
                            $pcTarget !== null
                            &&
                            $pcTarget !== $pcFinal
                        ) {
                            $validationStatus =
                                'pc_changed';

                            $validationNotes[] =
                                'PC target rotasi berbeda dengan PC final.';

                            $statistics[
                                'pc_changed'
                            ]++;
                        }
                    }

                    if ($isP1) {
                        $validationNotes[] =
                            'Kendaraan P1 dengan tujuan fleksibel; profil jarak tidak diperlukan.';
                    } elseif ($companyPlaceholder) {
                        $validationStatus =
                            'company_pending';

                        $validationNotes[] =
                            'Perusahaan masih berupa placeholder.';
                    } elseif (
                        $companyName !== ''
                        &&
                        $company === null
                    ) {
                        $validationStatus =
                            'company_unresolved';

                        $validationNotes[] =
                            'Nama perusahaan tidak ditemukan pada MASTER PERUSAHAAN.';
                    }

                    FleetGroupingAssignment::query()
                        ->create([
                            'grouping_period_id' =>
                                $groupingPeriod->id,

                            'vehicle_id' =>
                                $vehicle->id,

                            'company_id' =>
                                $isP1
                                    ? null
                                    : $company?->id,

                            'terminal_id' =>
                                $terminal->id,

                            'operational_type' =>
                                $operationalType,

                            'operator_name_snapshot' =>
                                $operatorName,

                            'pc_initial' =>
                                $pcInitial,

                            'pc_target' =>
                                $pcTarget,

                            'pc_final' =>
                                $pcFinal,

                            'plate_number_snapshot' =>
                                $plateNumber,

                            'company_name_snapshot' =>
                                !$isP1
                                &&
                                $companyName !== ''
                                    ? $companyName
                                    : null,

                            'terminal_name_snapshot' =>
                                $terminalName,

                            'source_rotation_row' =>
                                $rotationItem['row']
                                ?? null,

                            'source_final_row' =>
                                $finalItem['row']
                                ?? null,

                            'validation_status' =>
                                $validationStatus,

                            'validation_notes' =>
                                count($validationNotes) > 0
                                    ? implode(
                                        ' ',
                                        $validationNotes
                                    )
                                    : null,
                        ]);

                    $statistics[
                        'assignments_created'
                    ]++;

                    $statistics[
                        'pc_counts'
                    ][$pcFinal] =
                        (
                            $statistics[
                                'pc_counts'
                            ][$pcFinal]
                            ?? 0
                        )
                        +
                        1;

                    /*
                     * Hubungan perusahaan dan TLPG.
                     */
                    if (
                        !$isP1
                        &&
                        $company !== null
                    ) {
                        $pairKey =
                            $company->id
                            .
                            ':'
                            .
                            $terminal->id;

                        $companyTerminalPairs[
                            $pairKey
                        ] = [
                            'company_id' =>
                                $company->id,

                            'terminal_id' =>
                                $terminal->id,
                        ];

                        $companyTerminalCounts[
                            $company->id
                        ][$terminal->id] =
                            (
                                $companyTerminalCounts[
                                    $company->id
                                ][$terminal->id]
                                ?? 0
                            )
                            +
                            1;
                    }
                }

                /*
                 * TLPG wajib cocok seluruhnya.
                 */
                if (
                    count($unresolvedTerminals) > 0
                ) {
                    throw new RuntimeException(
                        'TLPG tidak ditemukan pada MASTER TLPG: '
                        .
                        implode(
                            ', ',
                            array_keys(
                                $unresolvedTerminals
                            )
                        )
                    );
                }

                $statistics[
                    'company_unresolved'
                ] =
                    count($unresolvedCompanies);

                /*
                |--------------------------------------------------------------------------
                | Buat profil hubungan perusahaan–TLPG
                |--------------------------------------------------------------------------
                */

                foreach (
                    $companyTerminalPairs
                    as $pair
                ) {
                    $profile =
                        FleetDistanceProfile::query()
                            ->firstOrNew([
                                'company_id' =>
                                    $pair['company_id'],

                                'terminal_id' =>
                                    $pair['terminal_id'],
                            ]);

                    if (!$profile->exists) {
                        $profile->distance_source =
                            'spreadsheet_import';

                        $statistics[
                            'distance_profiles_created'
                        ]++;
                    }

                    $profile->is_active =
                        true;

                    $profile->save();
                }

                /*
                 * default_terminal_id hanya diisi ketika satu perusahaan
                 * secara konsisten memiliki satu TLPG.
                 */
                foreach (
                    $companyTerminalCounts
                    as $companyId => $terminalCounts
                ) {
                    if (
                        count($terminalCounts) === 1
                    ) {
                        $terminalId =
                            (int) array_key_first(
                                $terminalCounts
                            );

                        FleetCompany::query()
                            ->whereKey(
                                $companyId
                            )
                            ->update([
                                'default_terminal_id' =>
                                    $terminalId,
                            ]);
                    } else {
                        $statistics[
                            'company_multiple_terminals'
                        ]++;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Sinkronkan kendaraan aktif menjadi tepat 333
                |--------------------------------------------------------------------------
                */

                if ($syncSnapshot) {
                    $statistics[
                        'vehicles_deactivated'
                    ] =
                        FleetVehicle::query()
                            ->whereNotIn(
                                'normalized_plate_number',
                                $officialPlateNumbers
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->update([
                                'is_active' =>
                                    false,

                                'effective_until' =>
                                    $effectiveDate,
                            ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi akhir
                |--------------------------------------------------------------------------
                */

                $assignmentCount =
                    FleetGroupingAssignment::query()
                        ->where(
                            'grouping_period_id',
                            $groupingPeriod->id
                        )
                        ->count();

                if (
                    $assignmentCount
                    !==
                    $officialVehicleCount
                ) {
                    throw new RuntimeException(
                        'Jumlah assignment tidak sesuai. '
                        .
                        'Seharusnya '
                        .
                        $officialVehicleCount
                        .
                        ', tersimpan '
                        .
                        $assignmentCount
                        .
                        '.'
                    );
                }

                $activeOfficialCount =
                    FleetVehicle::query()
                        ->whereIn(
                            'normalized_plate_number',
                            $officialPlateNumbers
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->count();

                if (
                    $activeOfficialCount
                    !==
                    $officialVehicleCount
                ) {
                    throw new RuntimeException(
                        'Jumlah kendaraan aktif resmi tidak sesuai.'
                    );
                }

                if ($syncSnapshot) {
                    $totalActiveVehicleCount =
                        FleetVehicle::query()
                            ->where(
                                'is_active',
                                true
                            )
                            ->count();

                    if (
                        $totalActiveVehicleCount
                        !==
                        $officialVehicleCount
                    ) {
                        throw new RuntimeException(
                            'Total kendaraan aktif bukan '
                            .
                            $officialVehicleCount
                            .
                            '.'
                        );
                    }
                }

                $savedP1AssignmentCount =
                    FleetGroupingAssignment::query()
                        ->where(
                            'grouping_period_id',
                            $groupingPeriod->id
                        )
                        ->where(
                            'operational_type',
                            FleetVehicle::TYPE_P1
                        )
                        ->count();

                if (
                    $savedP1AssignmentCount
                    !==
                    $officialP1Count
                ) {
                    throw new RuntimeException(
                        'Jumlah assignment P1 tidak sesuai. '
                        .
                        'Seharusnya '
                        .
                        $officialP1Count
                        .
                        ', tersimpan '
                        .
                        $savedP1AssignmentCount
                        .
                        '.'
                    );
                }

                $savedP2AssignmentCount =
                    $assignmentCount
                    -
                    $savedP1AssignmentCount;

                if (
                    $savedP2AssignmentCount
                    !==
                    $officialP2Count
                ) {
                    throw new RuntimeException(
                        'Jumlah assignment P2 tidak sesuai.'
                    );
                }

                ksort(
                    $statistics['pc_counts']
                );

                /*
                |--------------------------------------------------------------------------
                | Tandai batch sudah diimpor
                |--------------------------------------------------------------------------
                */

                $lockedBatch->forceFill([
                    'status' =>
                        'imported',

                    'imported_by' =>
                        $userId,

                    'imported_at' =>
                        now(),

                    'notes' =>
                        json_encode(
                            $statistics,
                            JSON_UNESCAPED_UNICODE
                            |
                            JSON_UNESCAPED_SLASHES
                        ),
                ])->save();

                return [
                    ...$statistics,

                    'batch_id' =>
                        $lockedBatch->id,

                    'grouping_period_id' =>
                        $groupingPeriod->id,

                    'grouping_period_name' =>
                        $groupingPeriod->name,

                    'unresolved_companies' =>
                        array_keys(
                            $unresolvedCompanies
                        ),
                ];
            },
            3
        );
    }

    /**
     * Mencari TLPG menggunakan nama normal
     * atau nama yang telah dibersihkan.
     */
    private function resolveTerminal(
        string $name,
        array $exactMap,
        array $looseMap
    ): ?FleetTerminal {
        if ($name === '') {
            return null;
        }

        $normalized =
            FleetTerminal::normalizeName(
                $name
            );

        if (
            isset(
                $exactMap[$normalized]
            )
        ) {
            return $exactMap[
                $normalized
            ];
        }

        $looseKey =
            $this->terminalMatchKey(
                $name
            );

        $matches =
            $looseMap[$looseKey]
            ?? [];

        return count($matches) === 1
            ? $matches[0]
            : null;
    }

    /**
     * Mencari perusahaan menggunakan nama normal
     * atau nama yang telah dibersihkan.
     */
    private function resolveCompany(
        string $name,
        array $exactMap,
        array $looseMap
    ): ?FleetCompany {
        if ($name === '') {
            return null;
        }

        $normalized =
            FleetCompany::normalizeName(
                $name
            );

        if (
            isset(
                $exactMap[$normalized]
            )
        ) {
            return $exactMap[
                $normalized
            ];
        }

        $looseKey =
            $this->companyMatchKey(
                $name
            );

        $matches =
            $looseMap[$looseKey]
            ?? [];

        return count($matches) === 1
            ? $matches[0]
            : null;
    }

    private function terminalMatchKey(
        string $value
    ): string {
        $value =
            mb_strtoupper(
                trim($value),
                'UTF-8'
            );

        $value =
            preg_replace(
                '/[^A-Z0-9]+/u',
                ' ',
                $value
            );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            )
        );
    }

    /**
     * Menyamakan variasi:
     *
     * SPPBE-PT. Lombok Putra Gas
     * PT LOMBOK PUTRA GAS
     */
    private function companyMatchKey(
        string $value
    ): string {
        $value =
            mb_strtoupper(
                trim($value),
                'UTF-8'
            );

        $value =
            preg_replace(
                '/\bSPPBE\b|\bSPBE\b/u',
                ' ',
                $value
            );

        $value =
            preg_replace(
                '/[^A-Z0-9]+/u',
                ' ',
                (string) $value
            );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            )
        );
    }
}