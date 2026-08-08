<?php

namespace App\Jobs;

use App\Models\FleetGoogleSyncLog;
use App\Models\User;
use App\Services\MasterFleet\MasterFleetGoogleWorkspaceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateEvidenceFoldersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Queue worker tetap menjadi batas utama.
     */
    public int $timeout = 21600;

    public int $tries = 3;

    public bool $failOnTimeout = false;

    /**
     * Mencegah tanggal yang sama masuk antrean lebih dari sekali.
     */
    public int $uniqueFor = 28800;

    /**
     * Jeda retry: 1 menit, 5 menit, lalu 15 menit.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [
            60,
            300,
            900,
        ];
    }

    public function uniqueId(): string
    {
        return $this->userId
            . ':'
            . $this->workspaceDate;
    }

    public function __construct(
        public readonly int $userId,
        public readonly string $workspaceDate
    ) {
        $this->onConnection('database');
        $this->onQueue('evidence');
    }

    public function handle(
        MasterFleetGoogleWorkspaceService $service
    ): void {
        $user = User::query()->findOrFail(
            $this->userId
        );

        $service->generateEvidenceFolders(
            $user,
            Carbon::createFromFormat(
                'Y-m-d',
                $this->workspaceDate
            )->startOfDay()
        );
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $message = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $exception?->getMessage()
                ?? 'Background worker Evidence berhenti.'
            )
            ?? ''
        );

        FleetGoogleSyncLog::query()
            ->where(
                'sync_type',
                'evidence_folders'
            )
            ->where(
                'status',
                'running'
            )
            ->whereDate(
                'target_date',
                $this->workspaceDate
            )
            ->latest(
                'id'
            )
            ->first()
            ?->update([
                'status' => 'failed',
                'message' => mb_substr(
                    $message,
                    0,
                    5000
                ),
                'finished_at' => now(),
            ]);
    }
}
