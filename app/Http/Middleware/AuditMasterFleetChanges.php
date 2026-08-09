<?php

namespace App\Http\Middleware;

use App\Models\MasterFleetAudit;
use App\Support\MasterFleet\FleetType;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMasterFleetChanges
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $routeName =
            $request->route()?->getName();

        if (
            !is_string($routeName)
            ||
            !str_starts_with(
                $routeName,
                'master-fleet.'
            )
            ||
            in_array(
                $request->method(),
                [
                    'GET',
                    'HEAD',
                    'OPTIONS',
                ],
                true
            )
            ||
            $this->shouldIgnore(
                $routeName
            )
        ) {
            return $next($request);
        }

        $subject =
            $this->resolveSubject(
                $request
            );

        $before =
            $subject instanceof Model
                ? $this->snapshot(
                    $subject
                )
                : null;

        $response =
            $next($request);

        if (
            $response->getStatusCode()
            >= 400
        ) {
            return $response;
        }

        try {
            $this->writeAudit(
                request:
                    $request,
                routeName:
                    $routeName,
                subject:
                    $subject,
                before:
                    $before
            );
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function shouldIgnore(
        string $routeName
    ): bool {
        return in_array(
            $routeName,
            [
                'master-fleet.import.preview',
            ],
            true
        );
    }

    private function writeAudit(
        Request $request,
        string $routeName,
        ?Model $subject,
        ?array $before
    ): void {
        $definition =
            $this->definition(
                $routeName
            );

        $after = null;

        if ($subject instanceof Model) {
            try {
                $subject->refresh();

                $after =
                    $this->snapshot(
                        $subject
                    );
            } catch (Throwable) {
                $after = null;
            }
        }

        $user =
            $request->user();

        $subjectLabel =
            $this->subjectLabel(
                $subject,
                $before,
                $after
            );

        $fleetType =
            $this->resolveFleetType(
                $request,
                $routeName,
                $subject,
                $before,
                $after
            );

        MasterFleetAudit::query()
            ->create([
                'occurred_at' =>
                    now(),

                'user_id' =>
                    $user?->id,

                'user_name' =>
                    $user?->name,

                'user_email' =>
                    $user?->email,

                'fleet_type' =>
                    $fleetType,

                'module' =>
                    $definition[
                        'module'
                    ],

                'action' =>
                    $definition[
                        'action'
                    ],

                'route_name' =>
                    $routeName,

                'subject_type' =>
                    $subject
                        ? class_basename(
                            $subject
                        )
                        : null,

                'subject_id' =>
                    $subject
                        ? (string) $subject
                            ->getKey()
                        : null,

                'subject_label' =>
                    $subjectLabel,

                'description' =>
                    $definition[
                        'description'
                    ]
                    .
                    (
                        $subjectLabel
                            ? ' — '
                                .
                                $subjectLabel
                            : ''
                    ),

                'before_data' =>
                    $before,

                'after_data' =>
                    $after,

                'meta' =>
                    $this->requestMeta(
                        $request
                    ),

                'ip_address' =>
                    $request->ip(),
            ]);
    }

    private function definition(
        string $routeName
    ): array {
        $map = [
            'master-fleet.terminals.store' => [
                'Master Terminal',
                'Tambah',
                'Menambah TLPG / Terminal',
            ],

            'master-fleet.terminals.update' => [
                'Master Terminal',
                'Ubah',
                'Mengubah TLPG / Terminal',
            ],

            'master-fleet.terminals.toggle-active' => [
                'Master Terminal',
                'Status',
                'Mengubah status TLPG / Terminal',
            ],

            'master-fleet.companies.store' => [
                'Master Perusahaan',
                'Tambah',
                'Menambah SPBE / Perusahaan',
            ],

            'master-fleet.companies.update' => [
                'Master Perusahaan',
                'Ubah',
                'Mengubah SPBE / Perusahaan',
            ],

            'master-fleet.companies.toggle-active' => [
                'Master Perusahaan',
                'Status',
                'Mengubah status SPBE / Perusahaan',
            ],

            'master-fleet.vehicles.store' => [
                'Master Kendaraan',
                'Tambah',
                'Menambah kendaraan',
            ],

            'master-fleet.vehicles.update' => [
                'Master Kendaraan',
                'Ubah',
                'Mengubah master kendaraan',
            ],

            'master-fleet.vehicles.toggle-active' => [
                'Master Kendaraan',
                'Status',
                'Mengubah status kendaraan',
            ],

            'master-fleet.fleet-type.update' => [
                'Master Kendaraan',
                'Jenis Armada',
                'Mengubah jenis armada kendaraan',
            ],

            'master-fleet.import.confirm' => [
                'Import Master Fleet',
                'Import',
                'Konfirmasi import Master Fleet',
            ],

            'master-fleet.grouping.create-draft' => [
                'Draft Grouping',
                'Buat Draft',
                'Membuat Draft Grouping',
            ],

            'master-fleet.grouping.calculate-distances' => [
                'Draft Grouping',
                'Profil Jarak',
                'Menghitung profil jarak',
            ],

            'master-fleet.grouping.generate' => [
                'Draft Grouping',
                'Generate',
                'Generate PC Final',
            ],

            'master-fleet.grouping.operator-count.update' => [
                'Draft Grouping',
                'Jumlah PC',
                'Mengubah jumlah PC',
            ],

            'master-fleet.grouping.assignments.update' => [
                'Draft Grouping',
                'Pindah PC',
                'Mengubah PC Final secara manual',
            ],

            'master-fleet.grouping.vehicles.store' => [
                'Draft Grouping',
                'Tambah Kendaraan',
                'Menambahkan kendaraan ke Draft Grouping',
            ],

            'master-fleet.grouping.publish' => [
                'Draft Grouping',
                'Publish',
                'Konfirmasi dan Publish PC Set',
            ],

            'master-fleet.grouping.reset' => [
                'Draft Grouping',
                'Reset',
                'Reset Draft Grouping dari PC Set Utama',
            ],

            'master-fleet.google-workspace.sync-spreadsheet' => [
                'Google Workspace',
                'Sinkronisasi',
                'Sinkronisasi spreadsheet Master Fleet',
            ],

            'master-fleet.google-workspace.generate-k302-daily' => [
                'Google Workspace',
                'Generate',
                'Generate K3.02 harian',
            ],

            'master-fleet.google-workspace.generate-evidence' => [
                'Google Workspace',
                'Generate',
                'Generate evidence Master Fleet',
            ],
        ];

        if (
            array_key_exists(
                $routeName,
                $map
            )
        ) {
            [
                $module,
                $action,
                $description,
            ] =
                $map[
                    $routeName
                ];

            return compact(
                'module',
                'action',
                'description'
            );
        }

        return [
            'module' =>
                'Master Fleet',

            'action' =>
                mb_strtoupper(
                    request()->method(),
                    'UTF-8'
                ),

            'description' =>
                'Perubahan Master Fleet',
        ];
    }

    private function resolveSubject(
        Request $request
    ): ?Model {
        $parameters =
            $request->route()
                ?->parameters()
            ?? [];

        foreach (
            [
                'assignment',
                'vehicle',
                'terminal',
                'company',
                'period',
                'batch',
            ]
            as $key
        ) {
            $value =
                $parameters[
                    $key
                ]
                ?? null;

            if (
                $value
                instanceof Model
            ) {
                return $value;
            }
        }

        foreach (
            $parameters
            as $value
        ) {
            if (
                $value
                instanceof Model
            ) {
                return $value;
            }
        }

        return null;
    }

    private function snapshot(
        Model $model
    ): array {
        $attributes =
            $model->getAttributes();

        $result = [];

        foreach (
            $attributes
            as $key => $value
        ) {
            if (
                $this->isSensitiveKey(
                    (string) $key
                )
            ) {
                continue;
            }

            if (
                is_string($value)
                &&
                mb_strlen(
                    $value,
                    'UTF-8'
                ) > 2000
            ) {
                $value =
                    mb_substr(
                        $value,
                        0,
                        2000,
                        'UTF-8'
                    )
                    .
                    '…';
            }

            $result[
                $key
            ] =
                $value;
        }

        return $result;
    }

    private function requestMeta(
        Request $request
    ): array {
        $result = [];

        foreach (
            $request->except([
                '_token',
                '_method',
                'password',
                'password_confirmation',
            ])
            as $key => $value
        ) {
            if (
                $this->isSensitiveKey(
                    (string) $key
                )
            ) {
                continue;
            }

            if (
                is_scalar($value)
                ||
                $value === null
            ) {
                $result[
                    $key
                ] =
                    is_string($value)
                    &&
                    mb_strlen(
                        $value,
                        'UTF-8'
                    ) > 1000
                        ? mb_substr(
                            $value,
                            0,
                            1000,
                            'UTF-8'
                        )
                            .
                            '…'
                        : $value;
            }
        }

        return $result;
    }

    private function isSensitiveKey(
        string $key
    ): bool {
        $key =
            mb_strtolower(
                $key,
                'UTF-8'
            );

        foreach (
            [
                'password',
                'secret',
                'token',
                'credential',
                'authorization',
            ]
            as $needle
        ) {
            if (
                str_contains(
                    $key,
                    $needle
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function subjectLabel(
        ?Model $subject,
        ?array $before,
        ?array $after
    ): ?string {
        if (!$subject) {
            return null;
        }

        foreach (
            [
                'plate_number',
                'name',
                'title',
                'uuid',
                'period_name',
            ]
            as $key
        ) {
            $value =
                $after[
                    $key
                ]
                ??
                $before[
                    $key
                ]
                ??
                null;

            if (
                is_scalar($value)
                &&
                trim(
                    (string) $value
                ) !== ''
            ) {
                return (string) $value;
            }
        }

        return class_basename(
            $subject
        )
        .
        ' #'
        .
        $subject->getKey();
    }

    private function resolveFleetType(
        Request $request,
        string $routeName,
        ?Model $subject,
        ?array $before,
        ?array $after
    ): string {
$candidate =
            $after[
                'fleet_type'
            ]
            ??
            $before[
                'fleet_type'
            ]
            ??
            null;

        if (
            is_string($candidate)
            &&
            trim($candidate) !== ''
        ) {
            return FleetType::normalize(
                $candidate
            );
        }

        return FleetType::current(
            $request
        );
    }
}
