<?php

namespace App\Services\MasterFleet;

use App\Models\FleetCompany;
use App\Models\FleetVehicle;
use App\Models\MasterFleetCompareBatch;
use App\Models\MasterFleetCompareRow;
use App\Support\MasterFleet\FleetType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class MasterFleetExcelCompareService
{
    private const ALIASES = [
        'plate_number' => ['NOPOL','NO POLISI','NOMOR POLISI','NO POL','PLATE NUMBER','LICENSE PLATE','NOPOL KENDARAAN'],
        'unit_code' => ['UNIT CODE','KODE UNIT','NO UNIT','NOMOR UNIT','UNIT','KODE KENDARAAN'],
        'company' => ['PERUSAHAAN','SPBE','NAMA PERUSAHAAN','COMPANY','VENDOR'],
        'operational_type' => ['TIPE OPERASIONAL','OPERATIONAL TYPE','JENIS OPERASIONAL','P1 P2','TYPE','TIPE'],
        'operator_name' => ['OPERATOR','NAMA OPERATOR','OPERATOR NAME'],
        'is_active' => ['STATUS','STATUS KENDARAAN','AKTIF','IS ACTIVE','ACTIVE'],
        'fleet_type' => ['JENIS ARMADA','ARMADA','FLEET TYPE','TIPE ARMADA'],
    ];

    public function compare(MasterFleetCompareBatch $batch): MasterFleetCompareBatch
    {
        if (!Schema::hasColumn('fleet_vehicles', 'fleet_type')) {
            throw new RuntimeException('Fitur Jenis Armada belum tersedia pada Master Fleet.');
        }

        $path = StoragePathResolver::resolve($batch->stored_path);
        $type = IOFactory::identify($path);
        $reader = IOFactory::createReader($type);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        try {
            [$sheet, $headerRow, $map] = $this->detectHeader($book->getAllSheets());
            $sources = $this->readRows($sheet, $headerRow, $map);

            if ($sources === []) {
                throw new RuntimeException('Tidak ada baris kendaraan yang dapat dibandingkan.');
            }

            return DB::transaction(function () use ($batch, $sheet, $headerRow, $map, $sources): MasterFleetCompareBatch {
                $batch = MasterFleetCompareBatch::query()->lockForUpdate()->findOrFail($batch->id);
                $batch->rows()->delete();

                $vehicles = FleetVehicle::query()
                    ->withoutGlobalScope('selected_fleet_type')
                    ->with('company:id,name')
                    ->where('fleet_type', $batch->fleet_type)
                    ->get();

                $companies = [];
                foreach (FleetCompany::query()->get(['id','name']) as $company) {
                    $companies[$this->normText($company->name)] = $company;
                }

                $byPlate = [];
                $byUnit = [];
                foreach ($vehicles as $vehicle) {
                    $p = FleetVehicle::normalizePlateNumber((string) $vehicle->plate_number);
                    if ($p !== '') {
                        $byPlate[$p] = $vehicle;
                    }
                    $u = $this->normUnit($vehicle->unit_code);
                    if ($u !== '') {
                        $byUnit[$u][] = $vehicle;
                    }
                }

                $plateCount = [];
                $unitCount = [];
                foreach ($sources as $source) {
                    $p = FleetVehicle::normalizePlateNumber((string) ($source['plate_number'] ?? ''));
                    $u = $this->normUnit($source['unit_code'] ?? null);
                    if ($p !== '') $plateCount[$p] = ($plateCount[$p] ?? 0) + 1;
                    if ($u !== '') $unitCount[$u] = ($unitCount[$u] ?? 0) + 1;
                }

                $matched = [];
                $summary = [
                    'total_source' => count($sources), 'same' => 0, 'changed' => 0,
                    'plate_change' => 0, 'new' => 0, 'missing' => 0,
                    'review' => 0, 'applyable' => 0, 'applied' => 0,
                ];

                foreach ($sources as $source) {
                    $result = $this->classify(
                        $source, $batch->fleet_type, $byPlate, $byUnit,
                        $companies, $plateCount, $unitCount
                    );

                    if ($result['vehicle']) {
                        $matched[$result['vehicle']->id] = true;
                    }

                    MasterFleetCompareRow::query()->create([
                        'batch_id' => $batch->id,
                        'source_row' => $source['_row'],
                        'status' => $result['status'],
                        'vehicle_id' => $result['vehicle']?->id,
                        'plate_number' => $result['plate_number'],
                        'unit_code' => $result['unit_code'],
                        'source_data' => $source,
                        'current_data' => $result['current'],
                        'proposed_data' => $result['proposed'],
                        'diff_data' => $result['diff'],
                        'can_apply' => $result['can_apply'],
                        'apply_message' => $result['message'] ?? null,
                    ]);

                    $summary[$result['status']] = ($summary[$result['status']] ?? 0) + 1;
                    if ($result['can_apply']) $summary['applyable']++;
                }

                foreach ($vehicles as $vehicle) {
                    if (!$vehicle->is_active || isset($matched[$vehicle->id])) continue;

                    MasterFleetCompareRow::query()->create([
                        'batch_id' => $batch->id,
                        'status' => 'missing',
                        'vehicle_id' => $vehicle->id,
                        'plate_number' => $vehicle->plate_number,
                        'unit_code' => $vehicle->unit_code,
                        'current_data' => $this->snapshot($vehicle),
                        'diff_data' => [
                            'Keberadaan' => [
                                'before' => 'Ada di Master Aktif',
                                'after' => 'Tidak ditemukan di file baru',
                            ],
                        ],
                        'can_apply' => false,
                        'apply_message' => 'Review saja; tidak dinonaktifkan otomatis.',
                    ]);
                    $summary['missing']++;
                }

                $batch->forceFill([
                    'status' => 'review',
                    'sheet_name' => $sheet->getTitle(),
                    'header_row' => $headerRow,
                    'header_map' => $map,
                    'summary' => $summary,
                ])->save();

                return $batch->refresh();
            }, 3);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function detectHeader(array $sheets): array
    {
        $best = null;

        foreach ($sheets as $sheet) {
            $maxRow = min(30, $sheet->getHighestDataRow());
            $maxCol = min(100, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));

            for ($row = 1; $row <= $maxRow; $row++) {
                $map = [];
                for ($col = 1; $col <= $maxCol; $col++) {
                    $value = trim((string) $sheet->getCell([$col, $row])->getFormattedValue());
                    if ($value === '') continue;
                    $field = $this->field($value);
                    if ($field && !isset($map[$field])) $map[$field] = $col;
                }

                if (!isset($map['plate_number']) && !isset($map['unit_code'])) continue;
                $score = count($map);
                if ($best === null || $score > $best['score']) {
                    $best = compact('sheet','row','map','score');
                }
            }
        }

        if (!$best) {
            throw new RuntimeException('Header Excel tidak dikenali. Minimal harus ada NOPOL atau UNIT CODE.');
        }

        return [$best['sheet'], $best['row'], $best['map']];
    }

    private function readRows($sheet, int $headerRow, array $map): array
    {
        $rows = [];
        $last = min($sheet->getHighestDataRow(), $headerRow + 10000);

        for ($r = $headerRow + 1; $r <= $last; $r++) {
            $item = ['_row' => $r];
            $has = false;

            foreach ($map as $field => $col) {
                $value = trim((string) $sheet->getCell([$col, $r])->getFormattedValue());
                $item[$field] = $value;
                if ($value !== '') $has = true;
            }

            if (!$has) continue;
            if (trim((string) ($item['plate_number'] ?? '')) === '' &&
                trim((string) ($item['unit_code'] ?? '')) === '') continue;

            $rows[] = $item;
        }

        return $rows;
    }

    private function classify(
        array $source,
        string $fleetType,
        array $byPlate,
        array $byUnit,
        array $companies,
        array $plateCount,
        array $unitCount
    ): array {
        $plate = trim((string) ($source['plate_number'] ?? ''));
        $plateKey = FleetVehicle::normalizePlateNumber($plate);
        $unit = trim((string) ($source['unit_code'] ?? ''));
        $unitKey = $this->normUnit($unit);
        $issues = [];

        if ($plateKey !== '' && ($plateCount[$plateKey] ?? 0) > 1) $issues[] = 'Nopol duplikat di file.';
        if ($unitKey !== '' && ($unitCount[$unitKey] ?? 0) > 1) $issues[] = 'Unit Code duplikat di file.';

        $unitVehicle = null;
        if ($unitKey !== '' && isset($byUnit[$unitKey])) {
            if (count($byUnit[$unitKey]) === 1) $unitVehicle = $byUnit[$unitKey][0];
            else $issues[] = 'Unit Code tidak unik di Master.';
        }

        $plateVehicle = $plateKey !== '' ? ($byPlate[$plateKey] ?? null) : null;
        if ($unitVehicle && $plateVehicle && $unitVehicle->id !== $plateVehicle->id) {
            $issues[] = 'Unit Code dan Nopol mengarah ke kendaraan berbeda.';
        }

        $vehicle = $unitVehicle ?? $plateVehicle;

        $sourceFleet = trim((string) ($source['fleet_type'] ?? ''));
        if ($sourceFleet !== '' && FleetType::normalize($sourceFleet) !== $fleetType) {
            $issues[] = 'Jenis Armada pada file berbeda dengan pilihan upload.';
        }

        $proposed = ['fleet_type' => $fleetType];
        if ($plate !== '') $proposed['plate_number'] = FleetVehicle::formatPlateNumber($plate);
        if ($unit !== '') $proposed['unit_code'] = $unit;

        $companyText = trim((string) ($source['company'] ?? ''));
        if ($companyText !== '') {
            $company = $companies[$this->normText($companyText)] ?? null;
            $proposed['company_name'] = $company?->name ?? $companyText;
            $proposed['company_id'] = $company?->id;
            if (!$company) $issues[] = 'Perusahaan/SPBE belum ada di Master Perusahaan.';
        }

        $opType = mb_strtoupper(trim((string) ($source['operational_type'] ?? '')), 'UTF-8');
        if ($opType !== '') {
            $proposed['operational_type'] = $opType;
            if (!in_array($opType, ['P1','P2'], true)) $issues[] = 'Tipe operasional harus P1/P2.';
        }

        $operator = trim((string) ($source['operator_name'] ?? ''));
        if ($operator !== '') $proposed['operator_name'] = mb_strtoupper($operator, 'UTF-8');

        $activeText = trim((string) ($source['is_active'] ?? ''));
        if ($activeText !== '') {
            $active = $this->parseActive($activeText);
            if ($active === null) $issues[] = 'Status aktif tidak dikenali.';
            else $proposed['is_active'] = $active;
        }

        if (!$vehicle) {
            if ($plateKey === '') $issues[] = 'Data baru wajib memiliki Nopol.';
            return [
                'status' => $issues ? 'review' : 'new',
                'vehicle' => null,
                'plate_number' => $plate !== '' ? FleetVehicle::formatPlateNumber($plate) : null,
                'unit_code' => $unit ?: null,
                'current' => null,
                'proposed' => $proposed,
                'diff' => $issues
                    ? ['Catatan Review' => ['before' => null, 'after' => implode(' ', $issues)]]
                    : ['Data Baru' => ['before' => null, 'after' => 'Belum ada di Master Fleet']],
                'can_apply' => $issues === [],
                'message' => $issues ? implode(' ', $issues) : null,
            ];
        }

        $current = $this->snapshot($vehicle);
        $diff = [];

        $this->diff($diff, 'Nopol', $current['plate_number'], $proposed['plate_number'] ?? null,
            array_key_exists('plate_number', $proposed),
            fn($v) => FleetVehicle::normalizePlateNumber((string) $v));
        $this->diff($diff, 'Unit Code', $current['unit_code'], $proposed['unit_code'] ?? null,
            array_key_exists('unit_code', $proposed), fn($v) => $this->normUnit($v));
        $this->diff($diff, 'Perusahaan', $current['company_name'], $proposed['company_name'] ?? null,
            array_key_exists('company_name', $proposed), fn($v) => $this->normText((string) $v));
        $this->diff($diff, 'Tipe Operasional', $current['operational_type'], $proposed['operational_type'] ?? null,
            array_key_exists('operational_type', $proposed), fn($v) => mb_strtoupper(trim((string) $v), 'UTF-8'));
        $this->diff($diff, 'Operator', $current['operator_name'], $proposed['operator_name'] ?? null,
            array_key_exists('operator_name', $proposed), fn($v) => mb_strtoupper(trim((string) $v), 'UTF-8'));
        $this->diff($diff, 'Status Aktif', $current['is_active'], $proposed['is_active'] ?? null,
            array_key_exists('is_active', $proposed), fn($v) => (bool) $v);

        if ($issues) {
            $diff['Catatan Review'] = ['before' => null, 'after' => implode(' ', $issues)];
            $status = 'review';
            $canApply = false;
        } elseif (!$diff) {
            $status = 'same';
            $canApply = false;
        } else {
            $status = isset($diff['Nopol']) ? 'plate_change' : 'changed';
            $canApply = true;
        }

        return [
            'status' => $status,
            'vehicle' => $vehicle,
            'plate_number' => $proposed['plate_number'] ?? $vehicle->plate_number,
            'unit_code' => $proposed['unit_code'] ?? $vehicle->unit_code,
            'current' => $current,
            'proposed' => $proposed,
            'diff' => $diff,
            'can_apply' => $canApply,
            'message' => $issues ? implode(' ', $issues) : null,
        ];
    }

    private function snapshot(FleetVehicle $vehicle): array
    {
        return [
            'plate_number' => $vehicle->plate_number,
            'unit_code' => $vehicle->unit_code,
            'company_id' => $vehicle->company_id,
            'company_name' => $vehicle->company?->name,
            'operational_type' => $vehicle->operational_type,
            'operator_name' => $vehicle->operator_name,
            'is_active' => (bool) $vehicle->is_active,
            'fleet_type' => $vehicle->fleet_type,
        ];
    }

    private function diff(array &$diff, string $label, mixed $before, mixed $after, bool $present, callable $norm): void
    {
        if (!$present) return;
        if ($norm($before) === $norm($after)) return;
        $diff[$label] = ['before' => $before, 'after' => $after];
    }

    private function field(string $header): ?string
    {
        $h = $this->normHeader($header);
        foreach (self::ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                if ($h === $this->normHeader($alias)) return $field;
            }
        }
        return null;
    }

    private function normHeader(string $value): string
    {
        return trim((string) preg_replace('/[^A-Z0-9]+/u', ' ',
            mb_strtoupper(trim($value), 'UTF-8')));
    }

    private function normText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ',
            mb_strtoupper(trim($value), 'UTF-8')));
    }

    private function normUnit(mixed $value): string
    {
        return mb_strtoupper(trim((string) ($value ?? '')), 'UTF-8');
    }

    private function parseActive(string $value): ?bool
    {
        $v = $this->normText($value);
        if (in_array($v, ['AKTIF','ACTIVE','YA','YES','Y','1'], true)) return true;
        if (in_array($v, ['NONAKTIF','NON AKTIF','INACTIVE','TIDAK','NO','N','0'], true)) return false;
        return null;
    }
}

final class StoragePathResolver
{
    public static function resolve(string $storedPath): string
    {
        $candidates = [
            storage_path('app/private/' . ltrim($storedPath, '/')),
            storage_path('app/' . ltrim($storedPath, '/')),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) return $path;
        }

        throw new RuntimeException('File staging Compare tidak ditemukan.');
    }
}
