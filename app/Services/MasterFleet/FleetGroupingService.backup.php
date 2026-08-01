<?php

namespace App\Services\MasterFleet;

use App\Models\FleetCompany;
use App\Models\FleetDistanceProfile;
use App\Models\FleetGroupingAssignment;
use App\Models\FleetGroupingPeriod;
use App\Models\FleetTerminal;
use App\Models\FleetVehicle;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FleetGroupingService
{
    /**
     * Membuat draft baru dari PC Set Utama yang sedang aktif.
     */
    public function createDraft(
        int $userId,
        string $name,
        string $effectiveDate,
        ?int $operatorCount = null
    ): FleetGroupingPeriod {
        return DB::transaction(
            function () use (
                $userId,
                $name,
                $effectiveDate,
                $operatorCount
            ): FleetGroupingPeriod {
                $existingDraft = FleetGroupingPeriod::query()
                    ->where('status', 'draft')
                    ->lockForUpdate()
                    ->first();

                if ($existingDraft !== null) {
                    throw new RuntimeException(
                        'Masih ada draft grouping yang belum dipublikasikan.'
                    );
                }

                $published = FleetGroupingPeriod::query()
                    ->where('status', 'published')
                    ->with([
                        'assignments.vehicle',
                        'assignments.company',
                        'assignments.terminal',
                    ])
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($published === null) {
                    throw new RuntimeException(
                        'PC Set Utama aktif belum tersedia.'
                    );
                }

                $resolvedOperatorCount = $this->normalizeOperatorCount(
                    $operatorCount
                    ?? $published->operator_count
                    ?? config('master-fleet.operator_count', 12)
                );

                $draft = FleetGroupingPeriod::query()->create([
                    'name' => trim($name),
                    'effective_date' => $effectiveDate,
                    'status' => 'draft',
                    'operator_count' => $resolvedOperatorCount,
                    'source_file_name' => $published->source_file_name,
                    'created_by' => $userId,
                    'notes' =>
                        'Draft dibuat dari PC Set Utama: '
                        . $published->name,
                ]);

                foreach ($published->assignments as $source) {
                    $vehicle = $source->vehicle;
                    $company = $source->company;
                    $terminal = $source->terminal;

                    FleetGroupingAssignment::query()->create([
                        'grouping_period_id' => $draft->id,
                        'vehicle_id' => $source->vehicle_id,

                        'company_id' =>
                            $source->company_id
                            ?? $vehicle?->company_id,

                        'terminal_id' => $source->terminal_id,

                        'distance_km' => $source->distance_km,

                        'distance_category' =>
                            $source->distance_category,

                        'distance_weight' =>
                            $source->distance_weight,

                        /*
                         * PC Final dari PC Set aktif menjadi PC Lama.
                         */
                        'pc_initial' => $source->pc_final,

                        /*
                         * PC Target tidak lagi digunakan.
                         */
                        'pc_target' => null,

                        /*
                         * Sebelum generate, PC Final sama dengan PC Lama.
                         */
                        'pc_final' => $source->pc_final,

                        'plate_number_snapshot' =>
                            $vehicle?->plate_number
                            ?? $source->plate_number_snapshot,

                        'company_name_snapshot' =>
                            $company?->name
                            ?? $source->company_name_snapshot,

                        'terminal_name_snapshot' =>
                            $terminal?->name
                            ?? $source->terminal_name_snapshot,

                        'source_rotation_row' =>
                            $source->source_rotation_row,

                        'source_final_row' =>
                            $source->source_final_row,

                        'validation_status' => 'unchanged',

                        'validation_notes' =>
                            'Disalin dari PC Set Utama aktif.',

                        'assignment_source' => 'copied',

                        'generated_at' => null,

                        'manually_adjusted_by' => null,
                        'manually_adjusted_at' => null,
                        'manual_adjustment_note' => null,
                    ]);
                }

                return $draft->refresh();
            },
            3
        );
    }

    /**
     * Mengubah jumlah PC pada periode draft.
     */
    public function updateOperatorCount(
        FleetGroupingPeriod $period,
        int $operatorCount
    ): FleetGroupingPeriod {
        return DB::transaction(
            function () use (
                $period,
                $operatorCount
            ): FleetGroupingPeriod {
                $draft = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($draft);

                $newOperatorCount =
                    $this->normalizeOperatorCount($operatorCount);

                $currentOperatorCount =
                    $this->resolveOperatorCount($draft);

                /*
                 * Jika jumlah PC dikurangi, pastikan tidak ada
                 * kendaraan yang masih berada di luar batas PC baru.
                 */
                if ($newOperatorCount < $currentOperatorCount) {
                    $hasAssignmentsOutsideRange =
                        FleetGroupingAssignment::query()
                            ->where(
                                'grouping_period_id',
                                $draft->id
                            )
                            ->where(
                                'pc_final',
                                '>',
                                $newOperatorCount
                            )
                            ->exists();

                    if ($hasAssignmentsOutsideRange) {
                        throw new RuntimeException(
                            'Jumlah PC belum dapat dikurangi karena masih ada '
                            . 'kendaraan pada PC '
                            . ($newOperatorCount + 1)
                            . ' sampai PC '
                            . $currentOperatorCount
                            . '. Pindahkan kendaraan tersebut terlebih dahulu.'
                        );
                    }
                }

                $draft->forceFill([
                    'operator_count' => $newOperatorCount,
                ])->save();

                return $draft->refresh();
            },
            3
        );
    }

    /**
     * Generate PC Final berdasarkan jarak dan bobot.
     */
    public function generate(
        FleetGroupingPeriod $period,
        bool $preserveManual = true
    ): array {
        return DB::transaction(
            function () use (
                $period,
                $preserveManual
            ): array {
                $lockedPeriod = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($lockedPeriod);

                $operatorCount =
                    $this->resolveOperatorCount($lockedPeriod);

                $assignments = FleetGroupingAssignment::query()
                    ->with([
                        'vehicle:id,plate_number,company_id,is_active',
                        'company:id,name,latitude,longitude,default_terminal_id',
                        'terminal:id,name,latitude,longitude',
                    ])
                    ->where(
                        'grouping_period_id',
                        $lockedPeriod->id
                    )
                    ->lockForUpdate()
                    ->get();

                if ($assignments->isEmpty()) {
                    throw new RuntimeException(
                        'Draft grouping belum memiliki kendaraan.'
                    );
                }

                $totalVehicles = $assignments->count();

                /*
                 * Contoh:
                 *
                 * 333 kendaraan dan 12 PC:
                 * 9 PC berisi 28 kendaraan
                 * 3 PC berisi 27 kendaraan
                 */
                $baseCapacity = intdiv(
                    $totalVehicles,
                    $operatorCount
                );

                $remainder =
                    $totalVehicles % $operatorCount;

                $capacities = [];
                $state = [];

                foreach (range(1, $operatorCount) as $pc) {
                    $capacities[$pc] =
                        $baseCapacity
                        + ($pc <= $remainder ? 1 : 0);

                    $state[$pc] = [
                        'count' => 0,
                        'weight' => 0,
                        'distance' => 0.0,
                    ];
                }

                $fixedAssignments = [];
                $candidates = [];

                $distanceMissing = 0;

                /*
                 * Membaca profil jarak seluruh kendaraan.
                 */
                foreach ($assignments as $assignment) {
                    $profile =
                        $this->resolveDistanceProfile($assignment);

                    $assignment->distance_km =
                        $profile['distance_km'];

                    $assignment->distance_category =
                        $profile['category'];

                    $assignment->distance_weight =
                        $profile['weight'];

                    $assignment->pc_target = null;

                    if ($profile['distance_km'] === null) {
                        $distanceMissing++;
                    }

                    /*
                     * Edit manual dipertahankan ketika checkbox
                     * Pertahankan Edit Manual aktif.
                     */
                    $isManual =
                        $preserveManual
                        && $assignment->assignment_source === 'manual'
                        && $this->validPc(
                            $assignment->pc_final,
                            $operatorCount
                        );

                    if ($isManual) {
                        $fixedAssignments[] = $assignment;

                        continue;
                    }

                    $candidates[] = [
                        'assignment' => $assignment,

                        'weight' => (int) (
                            $profile['weight'] ?? 1
                        ),

                        'distance' => (float) (
                            $profile['distance_km'] ?? 0
                        ),

                        'missing_distance' =>
                            $profile['distance_km'] === null,
                    ];
                }

                /*
                 * Masukkan beban edit manual ke state PC terlebih dahulu.
                 */
                foreach ($fixedAssignments as $assignment) {
                    $pc = (int) $assignment->pc_final;

                    $this->addLoad(
                        $state,
                        $pc,
                        (int) (
                            $assignment->distance_weight ?? 1
                        ),
                        (float) (
                            $assignment->distance_km ?? 0
                        )
                    );

                    $assignment->forceFill([
                        'pc_target' => null,
                        'validation_status' => 'manual',

                        'validation_notes' =>
                            'PC Final dipertahankan dari edit manual.',
                    ])->save();
                }

                /*
                 * Kendaraan berbobot besar diproses terlebih dahulu.
                 */
                usort(
                    $candidates,
                    function (
                        array $left,
                        array $right
                    ): int {
                        $weightComparison =
                            $right['weight']
                            <=> $left['weight'];

                        if ($weightComparison !== 0) {
                            return $weightComparison;
                        }

                        $distanceComparison =
                            $right['distance']
                            <=> $left['distance'];

                        if ($distanceComparison !== 0) {
                            return $distanceComparison;
                        }

                        return strcmp(
                            (string) $left['assignment']
                                ->plate_number_snapshot,

                            (string) $right['assignment']
                                ->plate_number_snapshot
                        );
                    }
                );

                $generated = 0;
                $moved = 0;
                $unchanged = 0;
                $newVehicles = 0;

                foreach ($candidates as $candidate) {
                    /** @var FleetGroupingAssignment $assignment */
                    $assignment = $candidate['assignment'];

                    $pc = $this->choosePc(
                        $state,
                        $capacities,
                        $operatorCount
                    );

                    $assignment->forceFill([
                        'pc_target' => null,
                        'pc_final' => $pc,

                        'assignment_source' =>
                            'generated',

                        'generated_at' => now(),

                        'manually_adjusted_by' => null,
                        'manually_adjusted_at' => null,
                        'manual_adjustment_note' => null,
                    ]);

                    if ($candidate['missing_distance']) {
                        $assignment->validation_status =
                            'distance_missing';

                        $assignment->validation_notes =
                            'Koordinat atau profil jarak belum lengkap.';
                    } elseif ($assignment->pc_initial === null) {
                        $assignment->validation_status =
                            'new_vehicle';

                        $assignment->validation_notes =
                            'Kendaraan baru, belum memiliki PC Lama.';

                        $newVehicles++;
                    } elseif (
                        (int) $assignment->pc_initial === $pc
                    ) {
                        $assignment->validation_status =
                            'unchanged';

                        $assignment->validation_notes =
                            'PC Final sama dengan PC Lama.';

                        $unchanged++;
                    } else {
                        $assignment->validation_status =
                            'moved';

                        $assignment->validation_notes =
                            'PC Final berubah dari PC Lama berdasarkan hasil generate.';

                        $moved++;
                    }

                    $assignment->save();

                    $this->addLoad(
                        $state,
                        $pc,
                        $candidate['weight'],
                        $candidate['distance']
                    );

                    $generated++;
                }

                return [
                    'total' => $totalVehicles,
                    'operator_count' => $operatorCount,
                    'generated' => $generated,

                    'manual_preserved' =>
                        count($fixedAssignments),

                    'moved' => $moved,
                    'unchanged' => $unchanged,
                    'new_vehicle' => $newVehicles,

                    'distance_missing' =>
                        $distanceMissing,

                    'pc_summary' => $state,
                ];
            },
            3
        );
    }

    /**
     * Mengubah PC Final secara manual.
     */
    public function updateManualPc(
        FleetGroupingPeriod $period,
        FleetGroupingAssignment $assignment,
        int $pcFinal,
        int $userId,
        ?string $note = null
    ): FleetGroupingAssignment {
        return DB::transaction(
            function () use (
                $period,
                $assignment,
                $pcFinal,
                $userId,
                $note
            ): FleetGroupingAssignment {
                $lockedPeriod = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($lockedPeriod);

                $operatorCount =
                    $this->resolveOperatorCount($lockedPeriod);

                if (
                    !$this->validPc(
                        $pcFinal,
                        $operatorCount
                    )
                ) {
                    throw new RuntimeException(
                        'PC Final tidak valid. Pilih PC 1 sampai PC '
                        . $operatorCount
                        . '.'
                    );
                }

                $lockedAssignment =
                    FleetGroupingAssignment::query()
                        ->lockForUpdate()
                        ->findOrFail($assignment->id);

                if (
                    (int) $lockedAssignment->grouping_period_id
                    !== (int) $lockedPeriod->id
                ) {
                    throw new RuntimeException(
                        'Kendaraan tidak termasuk dalam draft ini.'
                    );
                }

                $lockedAssignment->forceFill([
                    'pc_target' => null,
                    'pc_final' => $pcFinal,

                    'assignment_source' => 'manual',
                    'validation_status' => 'manual',

                    'validation_notes' =>
                        'PC Final diubah secara manual.',

                    'manually_adjusted_by' => $userId,
                    'manually_adjusted_at' => now(),

                    'manual_adjustment_note' =>
                        $note !== null && trim($note) !== ''
                            ? trim($note)
                            : null,
                ])->save();

                return $lockedAssignment->refresh();
            },
            3
        );
    }

    /**
     * Menambahkan nopol ke dalam draft.
     *
     * Method ini dapat digunakan untuk:
     * - menambahkan kendaraan yang benar-benar baru;
     * - memasukkan kendaraan Master Fleet yang belum tergrouping.
     */
    public function addVehicle(
        FleetGroupingPeriod $period,
        array $data,
        int $userId
    ): FleetGroupingAssignment {
        return DB::transaction(
            function () use (
                $period,
                $data,
                $userId
            ): FleetGroupingAssignment {
                $lockedPeriod = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($lockedPeriod);

                $operatorCount =
                    $this->resolveOperatorCount($lockedPeriod);

                $pcFinal = (int) (
                    $data['pc_final'] ?? 0
                );

                if (
                    !$this->validPc(
                        $pcFinal,
                        $operatorCount
                    )
                ) {
                    throw new RuntimeException(
                        'PC Final tidak valid. Pilih PC 1 sampai PC '
                        . $operatorCount
                        . '.'
                    );
                }

                $normalizedPlate =
                    FleetVehicle::normalizePlateNumber(
                        (string) (
                            $data['plate_number'] ?? ''
                        )
                    );

                if ($normalizedPlate === '') {
                    throw new RuntimeException(
                        'Format nopol tidak valid.'
                    );
                }

                $vehicle = FleetVehicle::query()
                    ->where(
                        'normalized_plate_number',
                        $normalizedPlate
                    )
                    ->first();

                if ($vehicle === null) {
                    $vehicle = new FleetVehicle();
                }

                $vehicle->plate_number =
                    FleetVehicle::formatPlateNumber(
                        (string) $data['plate_number']
                    );

                $vehicle->company_id =
                    !empty($data['company_id'])
                        ? (int) $data['company_id']
                        : null;

                $vehicle->is_active = true;

                if ($vehicle->effective_from === null) {
                    $vehicle->effective_from =
                        $lockedPeriod->effective_date
                        ?? now()->toDateString();
                }

                $vehicle->effective_until = null;

                $vehicle->save();

                $alreadyExists =
                    FleetGroupingAssignment::query()
                        ->where(
                            'grouping_period_id',
                            $lockedPeriod->id
                        )
                        ->where(
                            'vehicle_id',
                            $vehicle->id
                        )
                        ->exists();

                if ($alreadyExists) {
                    throw new RuntimeException(
                        'Nopol tersebut sudah ada dalam draft.'
                    );
                }

                $company = !empty($data['company_id'])
                    ? FleetCompany::query()->find(
                        (int) $data['company_id']
                    )
                    : null;

                $terminal = FleetTerminal::query()
                    ->findOrFail(
                        (int) $data['terminal_id']
                    );

                $assignment =
                    FleetGroupingAssignment::query()->create([
                        'grouping_period_id' =>
                            $lockedPeriod->id,

                        'vehicle_id' => $vehicle->id,
                        'company_id' => $company?->id,
                        'terminal_id' => $terminal->id,

                        /*
                         * Kendaraan baru belum memiliki PC Lama.
                         */
                        'pc_initial' => null,
                        'pc_target' => null,
                        'pc_final' => $pcFinal,

                        'plate_number_snapshot' =>
                            $vehicle->plate_number,

                        'company_name_snapshot' =>
                            $company?->name,

                        'terminal_name_snapshot' =>
                            $terminal->name,

                        'validation_status' => 'manual',

                        'validation_notes' =>
                            'Kendaraan ditambahkan secara manual ke draft.',

                        'assignment_source' => 'manual',

                        'generated_at' => null,

                        'manually_adjusted_by' => $userId,
                        'manually_adjusted_at' => now(),

                        'manual_adjustment_note' =>
                            trim(
                                (string) (
                                    $data['note'] ?? ''
                                )
                            ) ?: null,
                    ]);

                $profile =
                    $this->resolveDistanceProfile($assignment);

                $assignment->forceFill([
                    'distance_km' =>
                        $profile['distance_km'],

                    'distance_category' =>
                        $profile['category'],

                    'distance_weight' =>
                        $profile['weight'],
                ])->save();

                return $assignment->refresh();
            },
            3
        );
    }

    /**
     * Menghitung dan mengisi profil jarak seluruh kendaraan
     * dalam draft tanpa mengubah PC Lama maupun PC Final.
     */
    public function calculateDistances(
        FleetGroupingPeriod $period
    ): array {
        return DB::transaction(
            function () use ($period): array {
                $lockedPeriod =
                    FleetGroupingPeriod::query()
                        ->lockForUpdate()
                        ->findOrFail($period->id);

                $this->ensureDraft($lockedPeriod);

                $assignments =
                    FleetGroupingAssignment::query()
                        ->with([
                            'vehicle:id,plate_number,company_id,is_active',
                            'company:id,name,latitude,longitude,default_terminal_id',
                            'terminal:id,name,latitude,longitude',
                        ])
                        ->where(
                            'grouping_period_id',
                            $lockedPeriod->id
                        )
                        ->lockForUpdate()
                        ->get();

                if ($assignments->isEmpty()) {
                    throw new RuntimeException(
                        'Draft grouping belum memiliki kendaraan.'
                    );
                }

                $total = 0;
                $filled = 0;
                $newlyFilled = 0;
                $alreadyFilled = 0;
                $missing = 0;

                foreach ($assignments as $assignment) {
                    $total++;

                    $previousDistance =
                        $assignment->distance_km;

                    $profile =
                        $this->resolveDistanceProfile(
                            $assignment
                        );

                    /*
                    * Hanya memperbarui data profil jarak.
                    *
                    * Tidak mengubah:
                    * - pc_initial / PC Lama
                    * - pc_final / PC Final
                    * - assignment_source
                    * - validation_status
                    * - validation_notes
                    */
                    $assignment->forceFill([
                        'distance_km' =>
                            $profile['distance_km'],

                        'distance_category' =>
                            $profile['category'],

                        'distance_weight' =>
                            $profile['weight'],
                    ])->save();

                    if ($profile['distance_km'] === null) {
                        $missing++;

                        continue;
                    }

                    $filled++;

                    if ($previousDistance === null) {
                        $newlyFilled++;
                    } else {
                        $alreadyFilled++;
                    }
                }

                return [
                    'total' => $total,
                    'filled' => $filled,
                    'newly_filled' => $newlyFilled,
                    'already_filled' => $alreadyFilled,
                    'missing' => $missing,
                ];
            },
            3
        );
    }

    /**
     * Publish draft menjadi PC Set Utama baru.
     */
    public function publish(
        FleetGroupingPeriod $period,
        int $userId
    ): FleetGroupingPeriod {
        return DB::transaction(
            function () use (
                $period,
                $userId
            ): FleetGroupingPeriod {
                $lockedPeriod = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($lockedPeriod);

                $operatorCount =
                    $this->resolveOperatorCount($lockedPeriod);

                $assignments =
                    FleetGroupingAssignment::query()
                        ->where(
                            'grouping_period_id',
                            $lockedPeriod->id
                        )
                        ->lockForUpdate()
                        ->get();

                if ($assignments->isEmpty()) {
                    throw new RuntimeException(
                        'Draft grouping masih kosong.'
                    );
                }

                foreach ($assignments as $assignment) {
                    if (
                        !$this->validPc(
                            $assignment->pc_final,
                            $operatorCount
                        )
                    ) {
                        throw new RuntimeException(
                            'Masih ada kendaraan tanpa PC Final yang valid: '
                            . $assignment->plate_number_snapshot
                        );
                    }

                    if ($assignment->terminal_id === null) {
                        throw new RuntimeException(
                            'Masih ada kendaraan tanpa TLPG: '
                            . $assignment->plate_number_snapshot
                        );
                    }
                }

                /*
                 * Pastikan satu kendaraan hanya muncul satu kali.
                 */
                $duplicateVehicle = FleetGroupingAssignment::query()
                    ->select('vehicle_id')
                    ->where(
                        'grouping_period_id',
                        $lockedPeriod->id
                    )
                    ->whereNotNull('vehicle_id')
                    ->groupBy('vehicle_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->first();

                if ($duplicateVehicle !== null) {
                    throw new RuntimeException(
                        'Masih terdapat kendaraan ganda dalam draft.'
                    );
                }

                /*
                 * Arsipkan PC Set lama.
                 */
                FleetGroupingPeriod::query()
                    ->where('status', 'published')
                    ->where(
                        'id',
                        '!=',
                        $lockedPeriod->id
                    )
                    ->update([
                        'status' => 'archived',
                    ]);

                /*
                 * Aktifkan draft sebagai PC Set terbaru.
                 */
                $lockedPeriod->forceFill([
                    'status' => 'published',
                    'operator_count' => $operatorCount,
                    'published_by' => $userId,
                    'published_at' => now(),
                ])->save();

                return $lockedPeriod->refresh();
            },
            3
        );
    }

    /**
     * Mengembalikan draft ke kondisi PC Set Utama aktif.
     *
     * Hasil generate, edit manual, dan kendaraan tambahan
     * di draft akan dihapus.
     *
     * Master kendaraan tidak dihapus.
     */
    public function resetDraft(
        FleetGroupingPeriod $period
    ): array {
        return DB::transaction(
            function () use ($period): array {
                $draft = FleetGroupingPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($period->id);

                $this->ensureDraft($draft);

                $published = FleetGroupingPeriod::query()
                    ->where('status', 'published')
                    ->where(
                        'id',
                        '!=',
                        $draft->id
                    )
                    ->with([
                        'assignments.vehicle',
                        'assignments.company',
                        'assignments.terminal',
                    ])
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($published === null) {
                    throw new RuntimeException(
                        'PC Set Utama aktif tidak ditemukan.'
                    );
                }

                $removedCount =
                    FleetGroupingAssignment::query()
                        ->where(
                            'grouping_period_id',
                            $draft->id
                        )
                        ->count();

                FleetGroupingAssignment::query()
                    ->where(
                        'grouping_period_id',
                        $draft->id
                    )
                    ->delete();

                $copiedCount = 0;

                foreach ($published->assignments as $source) {
                    $vehicle = $source->vehicle;
                    $company = $source->company;
                    $terminal = $source->terminal;

                    FleetGroupingAssignment::query()->create([
                        'grouping_period_id' => $draft->id,
                        'vehicle_id' => $source->vehicle_id,

                        'company_id' =>
                            $source->company_id
                            ?? $vehicle?->company_id,

                        'terminal_id' => $source->terminal_id,

                        'distance_km' =>
                            $source->distance_km,

                        'distance_category' =>
                            $source->distance_category,

                        'distance_weight' =>
                            $source->distance_weight,

                        'pc_initial' => $source->pc_final,
                        'pc_target' => null,
                        'pc_final' => $source->pc_final,

                        'plate_number_snapshot' =>
                            $vehicle?->plate_number
                            ?? $source->plate_number_snapshot,

                        'company_name_snapshot' =>
                            $company?->name
                            ?? $source->company_name_snapshot,

                        'terminal_name_snapshot' =>
                            $terminal?->name
                            ?? $source->terminal_name_snapshot,

                        'source_rotation_row' =>
                            $source->source_rotation_row,

                        'source_final_row' =>
                            $source->source_final_row,

                        'validation_status' => 'unchanged',

                        'validation_notes' =>
                            'Disalin ulang dari PC Set Utama aktif.',

                        'assignment_source' => 'copied',

                        'generated_at' => null,

                        'manually_adjusted_by' => null,
                        'manually_adjusted_at' => null,
                        'manual_adjustment_note' => null,
                    ]);

                    $copiedCount++;
                }

                /*
                 * Reset jumlah PC sesuai PC Set Utama aktif.
                 */
                $publishedOperatorCount =
                    $this->resolveOperatorCount($published);

                $draft->forceFill([
                    'operator_count' =>
                        $publishedOperatorCount,

                    'notes' =>
                        'Draft direset dari PC Set Utama: '
                        . $published->name
                        . ' pada '
                        . now()->format('d-m-Y H:i'),
                ])->save();

                return [
                    'removed' => $removedCount,
                    'copied' => $copiedCount,

                    'operator_count' =>
                        $publishedOperatorCount,

                    'published_name' =>
                        $published->name,
                ];
            },
            3
        );
    }

    /**
     * Mendapatkan atau menghitung profil jarak.
     */
    private function resolveDistanceProfile(
        FleetGroupingAssignment $assignment
    ): array {
        $companyId =
            $assignment->company_id
            ?? $assignment->vehicle?->company_id;

        $company = $companyId
            ? FleetCompany::query()->find($companyId)
            : null;

        $terminalId =
            $assignment->terminal_id
            ?? $company?->default_terminal_id;

        $terminal = $terminalId
            ? FleetTerminal::query()->find($terminalId)
            : null;

        if (
            $company === null
            || $terminal === null
        ) {
            return $this->unknownProfile();
        }

        $profile = FleetDistanceProfile::query()
            ->firstOrNew([
                'company_id' => $company->id,
                'terminal_id' => $terminal->id,
            ]);

        /*
         * Gunakan profil yang sudah tersimpan.
         */
        if (
            $profile->distance_km !== null
            && $profile->weight !== null
        ) {
            return [
                'distance_km' =>
                    (float) $profile->distance_km,

                'category' =>
                    $profile->distance_category,

                'weight' =>
                    (int) $profile->weight,
            ];
        }

        /*
         * Cadangan sementara menggunakan jarak garis lurus.
         */
        $distance = $this->haversineDistance(
            $company->latitude,
            $company->longitude,
            $terminal->latitude,
            $terminal->longitude
        );

        if ($distance === null) {
            return $this->unknownProfile();
        }

        $classification =
            $this->classifyDistance($distance);

        $profile->forceFill([
            'distance_km' => $distance,

            'distance_category' =>
                $classification['category'],

            'weight' =>
                $classification['weight'],

            'distance_source' => 'haversine',
            'calculated_at' => now(),
            'is_active' => true,
        ])->save();

        return [
            'distance_km' => $distance,

            'category' =>
                $classification['category'],

            'weight' =>
                $classification['weight'],
        ];
    }

    /**
     * Menentukan kategori dan bobot jarak.
     */
    private function classifyDistance(
        float $distance
    ): array {
        $nearMax = (float) config(
            'master-fleet.distance.near_max_km',
            100
        );

        $mediumMax = (float) config(
            'master-fleet.distance.medium_max_km',
            170
        );

        $weights = config(
            'master-fleet.distance.weights',
            []
        );

        if ($distance <= $nearMax) {
            return [
                'category' => 'DEKAT',

                'weight' => (int) (
                    $weights['near'] ?? 1
                ),
            ];
        }

        if ($distance <= $mediumMax) {
            return [
                'category' => 'SEDANG',

                'weight' => (int) (
                    $weights['medium'] ?? 2
                ),
            ];
        }

        return [
            'category' => 'JAUH',

            'weight' => (int) (
                $weights['far'] ?? 3
            ),
        ];
    }

    /**
     * Profil jarak ketika data belum lengkap.
     */
    private function unknownProfile(): array
    {
        return [
            'distance_km' => null,
            'category' => 'BELUM TERSEDIA',

            'weight' => (int) config(
                'master-fleet.distance.weights.unknown',
                1
            ),
        ];
    }

    /**
     * Menghitung jarak garis lurus menggunakan Haversine.
     */
    private function haversineDistance(
        mixed $latitudeOne,
        mixed $longitudeOne,
        mixed $latitudeTwo,
        mixed $longitudeTwo
    ): ?float {
        if (
            $latitudeOne === null
            || $longitudeOne === null
            || $latitudeTwo === null
            || $longitudeTwo === null
        ) {
            return null;
        }

        if (
            !is_numeric($latitudeOne)
            || !is_numeric($longitudeOne)
            || !is_numeric($latitudeTwo)
            || !is_numeric($longitudeTwo)
        ) {
            return null;
        }

        $latitudeOne = (float) $latitudeOne;
        $longitudeOne = (float) $longitudeOne;
        $latitudeTwo = (float) $latitudeTwo;
        $longitudeTwo = (float) $longitudeTwo;

        if (
            $latitudeOne < -90
            || $latitudeOne > 90
            || $latitudeTwo < -90
            || $latitudeTwo > 90
            || $longitudeOne < -180
            || $longitudeOne > 180
            || $longitudeTwo < -180
            || $longitudeTwo > 180
        ) {
            return null;
        }

        $lat1 = deg2rad($latitudeOne);
        $lon1 = deg2rad($longitudeOne);
        $lat2 = deg2rad($latitudeTwo);
        $lon2 = deg2rad($longitudeTwo);

        $deltaLatitude = $lat2 - $lat1;
        $deltaLongitude = $lon2 - $lon1;

        $a =
            sin($deltaLatitude / 2) ** 2
            + cos($lat1)
            * cos($lat2)
            * sin($deltaLongitude / 2) ** 2;

        $centralAngle =
            2
            * asin(
                min(
                    1,
                    sqrt($a)
                )
            );

        return round(
            6371 * $centralAngle,
            2
        );
    }

    /**
     * Memilih PC dengan beban paling rendah
     * yang masih memiliki kapasitas.
     */
    private function choosePc(
        array $state,
        array $capacities,
        int $operatorCount
    ): int {
        $available = [];

        foreach (range(1, $operatorCount) as $pc) {
            if (
                $state[$pc]['count']
                < $capacities[$pc]
            ) {
                $available[] = $pc;
            }
        }

        /*
         * Cadangan ketika terdapat edit manual
         * yang menyebabkan kapasitas terlampaui.
         */
        if ($available === []) {
            $available = range(
                1,
                $operatorCount
            );
        }

        usort(
            $available,
            function (
                int $left,
                int $right
            ) use ($state): int {
                $weightComparison =
                    $state[$left]['weight']
                    <=> $state[$right]['weight'];

                if ($weightComparison !== 0) {
                    return $weightComparison;
                }

                $distanceComparison =
                    $state[$left]['distance']
                    <=> $state[$right]['distance'];

                if ($distanceComparison !== 0) {
                    return $distanceComparison;
                }

                $countComparison =
                    $state[$left]['count']
                    <=> $state[$right]['count'];

                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return $left <=> $right;
            }
        );

        return (int) $available[0];
    }

    /**
     * Menambahkan jumlah kendaraan dan beban ke state PC.
     */
    private function addLoad(
        array &$state,
        int $pc,
        int $weight,
        float $distance
    ): void {
        if (!isset($state[$pc])) {
            throw new RuntimeException(
                'PC ' . $pc . ' tidak tersedia dalam konfigurasi grouping.'
            );
        }

        $state[$pc]['count']++;
        $state[$pc]['weight'] += $weight;
        $state[$pc]['distance'] += $distance;
    }

    /**
     * Memeriksa apakah nomor PC valid.
     */
    private function validPc(
        mixed $pc,
        int $operatorCount
    ): bool {
        return is_numeric($pc)
            && (int) $pc >= 1
            && (int) $pc <= $operatorCount;
    }

    /**
     * Mengambil jumlah PC dari periode grouping.
     */
    private function resolveOperatorCount(
        FleetGroupingPeriod $period
    ): int {
        return $this->normalizeOperatorCount(
            $period->operator_count
            ?? config(
                'master-fleet.operator_count',
                12
            )
        );
    }

    /**
     * Validasi jumlah PC.
     */
    private function normalizeOperatorCount(
        mixed $operatorCount
    ): int {
        if (!is_numeric($operatorCount)) {
            throw new RuntimeException(
                'Jumlah PC harus berupa angka.'
            );
        }

        $operatorCount = (int) $operatorCount;

        if (
            $operatorCount < 1
            || $operatorCount > 50
        ) {
            throw new RuntimeException(
                'Jumlah PC harus antara 1 sampai 50.'
            );
        }

        return $operatorCount;
    }

    /**
     * Memastikan periode masih berstatus draft.
     */
    private function ensureDraft(
        FleetGroupingPeriod $period
    ): void {
        if ($period->status !== 'draft') {
            throw new RuntimeException(
                'Grouping ini bukan draft dan tidak dapat diedit.'
            );
        }
    }
}