<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMonitoringPermission
{
    /**
     * Memeriksa permission berdasarkan parameter {jenis}.
     *
     * Contoh:
     * monitoring.access:view
     * monitoring.access:export
     */
    public function handle(
        Request $request,
        Closure $next,
        string $action = 'view'
    ): Response {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $jenis = mb_strtolower(
            trim(
                (string) $request->route('jenis')
            ),
            'UTF-8'
        );

        $permissionMap = [
            'pelanggaran' => [
                'view' => 'pelanggaran.view',
                'export' => 'pelanggaran.export',
            ],

            'kendala' => [
                'view' => 'kendala.view',
                'export' => 'kendala.export',
            ],

            'accident' => [
                'view' => 'accident.view',
                'export' => 'accident.export',
            ],

            'errorlog' => [
                'view' => 'errorlog.view',
                'export' => 'errorlog.export',
            ],

            'skor-pengemudi' => [
                'view' => 'driver-score.view',
                'export' => 'driver-score.export',
            ],
        ];

        if (!isset($permissionMap[$jenis])) {
            abort(404);
        }

        if (!isset($permissionMap[$jenis][$action])) {
            abort(403, 'Jenis tindakan tidak diizinkan.');
        }

        $permission = $permissionMap[$jenis][$action];

        if (!$user->can($permission)) {
            abort(
                403,
                'Anda tidak memiliki izin untuk mengakses fitur ini.'
            );
        }

        return $next($request);
    }
}