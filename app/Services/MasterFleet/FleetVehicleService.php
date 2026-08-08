<?php

namespace App\Services\MasterFleet;

use App\Models\FleetCompany;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetVehicle;
use App\Models\FleetVehiclePlateHistory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FleetVehicleService
{
    public function create(
        array $data
    ): FleetVehicle {
        return DB::transaction(
            function () use ($data): FleetVehicle {
                $formattedPlate =
                    FleetVehicle::formatPlateNumber(
                        (string) $data['plate_number']
                    );

                $normalizedPlate =
                    FleetVehicle::normalizePlateNumber(
                        $formattedPlate
                    );

                if ($normalizedPlate === '') {
                    throw new RuntimeException(
                        'Format nopol tidak valid.'
                    );
                }

                $this->ensurePlateIsUnique(
                    $normalizedPlate
                );

                $isActive =
                    (bool) (
                        $data['is_active']
                        ?? true
                    );

                $operationalType =
                    $this->normalizeOperationalType(
                        $data['operational_type']
                        ?? FleetVehicle::TYPE_P2
                    );

                $companyId =
                    $operationalType === FleetVehicle::TYPE_P2
                    && !empty($data['company_id'])
                        ? (int) $data['company_id']
                        : null;

                $operatorName =
                    $operationalType === FleetVehicle::TYPE_P1
                        ? $this->nullableUppercaseString(
                            $data['operator_name']
                            ?? null
                        )
                        : null;

                $vehicle =
                    FleetVehicle::query()->create([
                        'plate_number' =>
                            $formattedPlate,

                        'normalized_plate_number' =>
                            $normalizedPlate,

                        'company_id' =>
                            $companyId,

                        'operational_type' =>
                            $operationalType,

                        'operator_name' =>
                            $operatorName,

                        'unit_code' =>
                            $this->nullableString(
                                $data['unit_code']
                                ?? null
                            ),

                        'effective_from' =>
                            $data['effective_from']
                            ?? now()->toDateString(),

                        'effective_until' =>
                            $isActive
                                ? null
                                : (
                                    $data['effective_until']
                                    ?? now()->toDateString()
                                ),

                        'is_active' =>
                            $isActive,

                        'notes' =>
                            $this->nullableString(
                                $data['notes']
                                ?? null
                            ),
                    ]);

                return $vehicle->refresh();
            },
            3
        );
    }

    /**
     * Memperbarui master kendaraan.
     *
     * Nopol baru disinkronkan hanya ke:
     * - PC Set Utama yang masih published;
     * - Draft Grouping yang masih draft.
     *
     * Grouping archived tidak diubah.
     */
    public function update(
        FleetVehicle $vehicle,
        array $data,
        int $userId
    ): FleetVehicle {
        return DB::transaction(
            function () use (
                $vehicle,
                $data,
                $userId
            ): FleetVehicle {
                $lockedVehicle =
                    FleetVehicle::query()
                        ->lockForUpdate()
                        ->findOrFail($vehicle->id);

                $oldPlate =
                    $lockedVehicle->plate_number;

                $oldNormalizedPlate =
                    $lockedVehicle
                        ->normalized_plate_number;

                $oldCompanyId =
                    $lockedVehicle->company_id;

                $oldOperationalType =
                    $lockedVehicle->operational_type
                    ?: FleetVehicle::TYPE_P2;

                $oldOperatorName =
                    $lockedVehicle->operator_name;

                $newPlate =
                    FleetVehicle::formatPlateNumber(
                        (string) $data['plate_number']
                    );

                $newNormalizedPlate =
                    FleetVehicle::normalizePlateNumber(
                        $newPlate
                    );

                if ($newNormalizedPlate === '') {
                    throw new RuntimeException(
                        'Format nopol tidak valid.'
                    );
                }

                $this->ensurePlateIsUnique(
                    $newNormalizedPlate,
                    $lockedVehicle->id
                );

                $newOperationalType =
                    $this->normalizeOperationalType(
                        $data['operational_type']
                        ?? FleetVehicle::TYPE_P2
                    );

                $newCompanyId =
                    $newOperationalType === FleetVehicle::TYPE_P2
                    && !empty($data['company_id'])
                        ? (int) $data['company_id']
                        : null;

                $newOperatorName =
                    $newOperationalType === FleetVehicle::TYPE_P1
                        ? $this->nullableUppercaseString(
                            $data['operator_name']
                            ?? null
                        )
                        : null;

                $plateChanged =
                    $oldNormalizedPlate
                    !==
                    $newNormalizedPlate;

                $companyChanged =
                    (int) ($oldCompanyId ?? 0)
                    !==
                    (int) ($newCompanyId ?? 0);

                $operationalTypeChanged =
                    $oldOperationalType
                    !==
                    $newOperationalType;

                $operatorNameChanged =
                    (string) ($oldOperatorName ?? '')
                    !==
                    (string) ($newOperatorName ?? '');

                if ($plateChanged) {
                    $reason =
                        trim(
                            (string) (
                                $data[
                                    'plate_change_reason'
                                ]
                                ?? ''
                            )
                        );

                    if ($reason === '') {
                        throw new RuntimeException(
                            'Alasan perubahan nopol wajib diisi.'
                        );
                    }
                }

                $isActive =
                    (bool) (
                        $data['is_active']
                        ?? false
                    );

                $lockedVehicle->forceFill([
                    'plate_number' =>
                        $newPlate,

                    'normalized_plate_number' =>
                        $newNormalizedPlate,

                    'company_id' =>
                        $newCompanyId,

                    'operational_type' =>
                        $newOperationalType,

                    'operator_name' =>
                        $newOperatorName,

                    'unit_code' =>
                        $this->nullableString(
                            $data['unit_code']
                            ?? null
                        ),

                    'effective_from' =>
                        $data['effective_from']
                        ?? $lockedVehicle
                            ->effective_from,

                    'effective_until' =>
                        $isActive
                            ? null
                            : (
                                $data['effective_until']
                                ?? $lockedVehicle
                                    ->effective_until
                                ?? now()->toDateString()
                            ),

                    'is_active' =>
                        $isActive,

                    'notes' =>
                        $this->nullableString(
                            $data['notes']
                            ?? null
                        ),
                ])->save();

                if ($plateChanged) {
                    FleetVehiclePlateHistory::query()
                        ->create([
                            'vehicle_id' =>
                                $lockedVehicle->id,

                            'old_plate_number' =>
                                $oldPlate,

                            'new_plate_number' =>
                                $lockedVehicle
                                    ->plate_number,

                            'old_normalized_plate_number' =>
                                $oldNormalizedPlate,

                            'new_normalized_plate_number' =>
                                $lockedVehicle
                                    ->normalized_plate_number,

                            'effective_date' =>
                                $data[
                                    'plate_change_effective_date'
                                ]
                                ?? now()->toDateString(),

                            'reason' =>
                                trim(
                                    (string) $data[
                                        'plate_change_reason'
                                    ]
                                ),

                            'changed_by' =>
                                $userId,
                        ]);
                }

                if (
                    $plateChanged
                    ||
                    $companyChanged
                    ||
                    $operationalTypeChanged
                    ||
                    $operatorNameChanged
                ) {
                    $this->syncCurrentAssignments(
                        vehicle:
                            $lockedVehicle,

                        plateChanged:
                            $plateChanged,

                        companyChanged:
                            $companyChanged,

                        operationalTypeChanged:
                            $operationalTypeChanged,

                        operatorNameChanged:
                            $operatorNameChanged
                    );
                }

                return $lockedVehicle->refresh();
            },
            3
        );
    }

    public function toggleActive(
        FleetVehicle $vehicle
    ): FleetVehicle {
        return DB::transaction(
            function () use (
                $vehicle
            ): FleetVehicle {
                $lockedVehicle =
                    FleetVehicle::query()
                        ->lockForUpdate()
                        ->findOrFail($vehicle->id);

                $newStatus =
                    !$lockedVehicle->is_active;

                $lockedVehicle->forceFill([
                    'is_active' =>
                        $newStatus,

                    'effective_from' =>
                        $newStatus
                            ? (
                                $lockedVehicle
                                    ->effective_from
                                ?? now()->toDateString()
                            )
                            : $lockedVehicle
                                ->effective_from,

                    'effective_until' =>
                        $newStatus
                            ? null
                            : now()->toDateString(),
                ])->save();

                return $lockedVehicle->refresh();
            },
            3
        );
    }

    private function syncCurrentAssignments(
        FleetVehicle $vehicle,
        bool $plateChanged,
        bool $companyChanged,
        bool $operationalTypeChanged,
        bool $operatorNameChanged
    ): void {
        $company =
            $vehicle->company_id
                ? FleetCompany::query()
                    ->find($vehicle->company_id)
                : null;

        $assignments =
            FleetGroupingAssignment::query()
                ->where(
                    'vehicle_id',
                    $vehicle->id
                )
                ->whereHas(
                    'groupingPeriod',
                    function ($query): void {
                        $query->whereIn(
                            'status',
                            [
                                'published',
                                'draft',
                            ]
                        );
                    }
                )
                ->lockForUpdate()
                ->get();

        foreach ($assignments as $assignment) {
            $updates = [];

            $notes = [];

            if ($plateChanged) {
                $updates[
                    'plate_number_snapshot'
                ] = $vehicle->plate_number;

                $notes[] =
                    'Nopol disinkronkan dari Master Kendaraan.';
            }

            if ($companyChanged) {
                $updates['company_id'] =
                    $vehicle->company_id;

                $updates[
                    'company_name_snapshot'
                ] = $company?->name;

                /*
                 * Profil jarak lama tidak lagi valid
                 * setelah perusahaan berubah.
                 */
                $updates['distance_km'] = null;

                $updates[
                    'distance_category'
                ] = null;

                $updates[
                    'distance_weight'
                ] = null;

                $notes[] =
                    'Perusahaan berubah; profil jarak perlu dihitung ulang.';
            }

            if ($operationalTypeChanged) {
                $updates['operational_type'] =
                    $vehicle->operational_type;

                $updates['company_id'] =
                    $vehicle->company_id;

                $updates[
                    'company_name_snapshot'
                ] = $company?->name;

                $updates[
                    'operator_name_snapshot'
                ] = $vehicle->operator_name;

                /*
                 * Profil jarak P2 lama tidak boleh terbawa ke P1.
                 * Saat P1 berubah menjadi P2, jarak juga harus
                 * dihitung ulang berdasarkan SPBE tujuan baru.
                 */
                $updates['distance_km'] = null;

                $updates[
                    'distance_category'
                ] = null;

                $updates[
                    'distance_weight'
                ] = null;

                $notes[] =
                    'Tipe operasional berubah menjadi '
                    . $vehicle->operational_type
                    . '; snapshot dan profil jarak disinkronkan.';
            }

            if (
                $operatorNameChanged
                && !$operationalTypeChanged
            ) {
                $updates[
                    'operator_name_snapshot'
                ] = $vehicle->operator_name;

                $notes[] =
                    'Nama operator P1 disinkronkan dari Master Kendaraan.';
            }

            if ($notes !== []) {
                $oldNotes =
                    trim(
                        (string) (
                            $assignment
                                ->validation_notes
                            ?? ''
                        )
                    );

                $updates[
                    'validation_notes'
                ] = trim(
                    $oldNotes
                    .
                    ($oldNotes !== '' ? ' ' : '')
                    .
                    implode(' ', $notes)
                );
            }

            if ($updates !== []) {
                $assignment
                    ->forceFill($updates)
                    ->save();
            }
        }
    }

    private function ensurePlateIsUnique(
        string $normalizedPlate,
        ?int $ignoreVehicleId = null
    ): void {
        $query =
            FleetVehicle::query()
                ->where(
                    'normalized_plate_number',
                    $normalizedPlate
                );

        if ($ignoreVehicleId !== null) {
            $query->where(
                'id',
                '!=',
                $ignoreVehicleId
            );
        }

        if ($query->exists()) {
            throw new RuntimeException(
                'Nopol tersebut sudah digunakan oleh kendaraan lain.'
            );
        }
    }

    private function nullableString(
        mixed $value
    ): ?string {
        $value =
            trim(
                (string) (
                    $value
                    ?? ''
                )
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function nullableUppercaseString(
        mixed $value
    ): ?string {
        $value =
            $this->nullableString(
                $value
            );

        return $value !== null
            ? mb_strtoupper(
                $value,
                'UTF-8'
            )
            : null;
    }

    private function normalizeOperationalType(
        mixed $value
    ): string {
        $value =
            mb_strtoupper(
                trim(
                    (string) $value
                ),
                'UTF-8'
            );

        if (
            !in_array(
                $value,
                [
                    FleetVehicle::TYPE_P1,
                    FleetVehicle::TYPE_P2,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Tipe operasional kendaraan tidak valid.'
            );
        }

        return $value;
    }
}
