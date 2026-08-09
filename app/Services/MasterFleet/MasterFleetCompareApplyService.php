<?php

namespace App\Services\MasterFleet;

use App\Models\FleetVehicle;
use App\Models\MasterFleetCompareBatch;
use App\Models\MasterFleetCompareRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MasterFleetCompareApplyService
{
    public function apply(
        MasterFleetCompareBatch $batch,
        array $rowIds,
        int $userId
    ): array {
        $rowIds = array_values(array_unique(array_map('intval', $rowIds)));

        if ($rowIds === []) {
            throw new RuntimeException('Pilih minimal satu perubahan.');
        }

        return DB::transaction(function () use ($batch, $rowIds, $userId): array {
            $batch = MasterFleetCompareBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $rows = MasterFleetCompareRow::query()
                ->where('batch_id', $batch->id)
                ->whereIn('id', $rowIds)
                ->where('can_apply', true)
                ->where('apply_status', 'pending')
                ->lockForUpdate()
                ->get();

            if ($rows->count() !== count($rowIds)) {
                throw new RuntimeException(
                    'Sebagian baris tidak valid, sudah diterapkan, atau tidak boleh diterapkan.'
                );
            }

            $vehicleService = app(FleetVehicleService::class);

            $created = 0;
            $updated = 0;
            $plateChanged = 0;

            foreach ($rows as $row) {
                $proposed = $row->proposed_data ?? [];

                if ($row->status === 'new') {
                    $plate = trim((string) ($proposed['plate_number'] ?? ''));

                    if ($plate === '') {
                        throw new RuntimeException('Data baru tanpa Nopol tidak dapat diterapkan.');
                    }

                    $vehicle = $vehicleService->create([
                        'plate_number' => $plate,
                        'company_id' => $proposed['company_id'] ?? null,
                        'unit_code' => $proposed['unit_code'] ?? null,
                        'effective_from' => now()->toDateString(),
                        'is_active' => $proposed['is_active'] ?? true,
                        'notes' => 'Dibuat melalui Compare Import: ' . $batch->original_name,
                    ]);

                    $extra = [];
                    if (Schema::hasColumn('fleet_vehicles', 'fleet_type')) {
                        $extra['fleet_type'] = $batch->fleet_type;
                    }
                    if (Schema::hasColumn('fleet_vehicles', 'operational_type') &&
                        isset($proposed['operational_type'])) {
                        $extra['operational_type'] = $proposed['operational_type'];
                    }
                    if (Schema::hasColumn('fleet_vehicles', 'operator_name') &&
                        isset($proposed['operator_name'])) {
                        $extra['operator_name'] = $proposed['operator_name'];
                    }

                    if ($extra !== []) {
                        $vehicle->forceFill($extra)->save();
                    }

                    $row->forceFill([
                        'vehicle_id' => $vehicle->id,
                        'apply_status' => 'applied',
                        'apply_message' => 'Kendaraan baru ditambahkan.',
                        'applied_at' => now(),
                    ])->save();

                    $created++;
                    continue;
                }

                $vehicle = FleetVehicle::query()
                    ->withoutGlobalScope('selected_fleet_type')
                    ->lockForUpdate()
                    ->find($row->vehicle_id);

                if (!$vehicle) {
                    throw new RuntimeException('Kendaraan Master untuk baris compare tidak ditemukan.');
                }

                $newPlate = $proposed['plate_number'] ?? $vehicle->plate_number;
                $isPlateChange =
                    FleetVehicle::normalizePlateNumber((string) $newPlate)
                    !== $vehicle->normalized_plate_number;

                $updatedVehicle = $vehicleService->update(
                    vehicle: $vehicle,
                    data: [
                        'plate_number' => $newPlate,
                        'company_id' => array_key_exists('company_id', $proposed)
                            ? $proposed['company_id']
                            : $vehicle->company_id,
                        'unit_code' => $proposed['unit_code'] ?? $vehicle->unit_code,
                        'effective_from' => $vehicle->effective_from?->toDateString()
                            ?? now()->toDateString(),
                        'effective_until' => $vehicle->effective_until?->toDateString(),
                        'is_active' => array_key_exists('is_active', $proposed)
                            ? (bool) $proposed['is_active']
                            : (bool) $vehicle->is_active,
                        'notes' => $vehicle->notes,
                        'plate_change_effective_date' => now()->toDateString(),
                        'plate_change_reason' => $isPlateChange
                            ? 'Compare Import Pengawas: ' . $batch->original_name
                            : null,
                    ],
                    userId: $userId
                );

                $extra = [];
                if (Schema::hasColumn('fleet_vehicles', 'fleet_type')) {
                    $extra['fleet_type'] = $batch->fleet_type;
                }
                if (Schema::hasColumn('fleet_vehicles', 'operational_type') &&
                    isset($proposed['operational_type'])) {
                    $extra['operational_type'] = $proposed['operational_type'];
                }
                if (Schema::hasColumn('fleet_vehicles', 'operator_name') &&
                    isset($proposed['operator_name'])) {
                    $extra['operator_name'] = $proposed['operator_name'];
                }
                if ($extra !== []) {
                    $updatedVehicle->forceFill($extra)->save();
                }

                $row->forceFill([
                    'apply_status' => 'applied',
                    'apply_message' => $isPlateChange
                        ? 'Ganti Nopol diterapkan dan histori Nopol dibuat.'
                        : 'Perubahan diterapkan.',
                    'applied_at' => now(),
                ])->save();

                $updated++;
                if ($isPlateChange) $plateChanged++;
            }

            $remaining = MasterFleetCompareRow::query()
                ->where('batch_id', $batch->id)
                ->where('can_apply', true)
                ->where('apply_status', 'pending')
                ->count();

            $applied = MasterFleetCompareRow::query()
                ->where('batch_id', $batch->id)
                ->where('apply_status', 'applied')
                ->count();

            $summary = $batch->summary ?? [];
            $summary['applied'] = $applied;

            $batch->forceFill([
                'status' => $remaining === 0 ? 'applied' : 'partially_applied',
                'summary' => $summary,
                'applied_by' => $userId,
                'applied_at' => now(),
            ])->save();

            return compact('created', 'updated', 'plateChanged', 'remaining');
        }, 3);
    }
}
