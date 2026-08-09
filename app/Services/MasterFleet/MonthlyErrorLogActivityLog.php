<?php

namespace App\Services\MasterFleet;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MonthlyErrorLogActivityLog
{
    /**
     * @param array<string, mixed> $result
     */
    public function recordSuccess(User $user, array $result): void
    {
        $action = !empty($result['created']) ? 'created' : 'reused';

        $entry = [
            'occurred_at' => now()->toIso8601String(),
            'occurred_label' => now()->format('d/m/Y H:i:s'),
            'action' => $action,
            'user_id' => $user->id,
            'user_name' => (string) ($user->name ?? 'User SIMOLA'),
            'month' => (string) ($result['month'] ?? ''),
            'month_label' => (string) ($result['month_label'] ?? ''),
            'root_folder_id' => (string) ($result['root_folder_id'] ?? ''),
            'root_folder_name' => (string) ($result['root_folder_name'] ?? ''),
            'spreadsheet_id' => (string) ($result['spreadsheet_id'] ?? ''),
            'spreadsheet_name' => (string) ($result['spreadsheet_name'] ?? ''),
            'spreadsheet_url' => (string) ($result['spreadsheet_url'] ?? ''),
            'google_email' => (string) ($result['google_email'] ?? ''),
            'message' => $action === 'created'
                ? 'Spreadsheet bulan berhasil dibuat.'
                : 'Spreadsheet bulan sudah ada dan dipakai ulang.',
        ];

        $this->append($entry);

        try {
            Log::info('SIMOLA Error Log Bulanan', [
                'action' => $action,
                'user_id' => $user->id,
                'month' => $entry['month'],
                'root_folder_id' => $entry['root_folder_id'],
                'spreadsheet_id' => $entry['spreadsheet_id'],
            ]);
        } catch (Throwable) {
            // Audit log tidak boleh mengubah hasil proses utama.
        }
    }

    public function recordFailure(
        User $user,
        ?string $month,
        ?string $rootFolderInput,
        Throwable $exception
    ): void {
        $entry = [
            'occurred_at' => now()->toIso8601String(),
            'occurred_label' => now()->format('d/m/Y H:i:s'),
            'action' => 'failed',
            'user_id' => $user->id,
            'user_name' => (string) ($user->name ?? 'User SIMOLA'),
            'month' => trim((string) $month),
            'month_label' => trim((string) $month),
            'root_folder_id' => '',
            'root_folder_name' => $this->compact((string) $rootFolderInput, 160),
            'spreadsheet_id' => '',
            'spreadsheet_name' => '',
            'spreadsheet_url' => '',
            'google_email' => '',
            'message' => $this->compact($exception->getMessage(), 500),
        ];

        $this->append($entry);

        try {
            Log::warning('SIMOLA Error Log Bulanan gagal', [
                'user_id' => $user->id,
                'month' => $entry['month'],
                'root_folder' => $entry['root_folder_name'],
                'error' => $entry['message'],
            ]);
        } catch (Throwable) {
            // Audit log tidak boleh mengubah alur error controller.
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(?int $limit = null): array
    {
        $limit = $limit ?? (int) config('errorlog-monthly.activity_ui_limit', 8);
        $limit = max(1, min($limit, 50));
        $disk = Storage::disk('local');
        $path = $this->path();

        if (!$disk->exists($path)) {
            return [];
        }

        try {
            $lines = preg_split('/\R/u', trim((string) $disk->get($path))) ?: [];
        } catch (Throwable $exception) {
            report($exception);
            return [];
        }

        $entries = [];
        foreach (array_reverse($lines) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            $entries[] = $decoded;
            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function append(array $entry): void
    {
        $disk = Storage::disk('local');
        $path = $this->path();
        $json = json_encode(
            $entry,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if (!is_string($json)) {
            return;
        }

        try {
            $disk->append($path, $json);
            $this->prune($disk, $path);
        } catch (Throwable $exception) {
            // Logging aktivitas tidak boleh menggagalkan proses utama generator.
            report($exception);
        }
    }

    private function prune($disk, string $path): void
    {
        $max = max(50, (int) config('errorlog-monthly.activity_log_max', 500));

        try {
            $content = trim((string) $disk->get($path));
            if ($content === '') {
                return;
            }

            $lines = preg_split('/\R/u', $content) ?: [];
            if (count($lines) <= $max) {
                return;
            }

            $disk->put($path, implode(PHP_EOL, array_slice($lines, -$max)) . PHP_EOL);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function path(): string
    {
        $path = trim((string) config(
            'errorlog-monthly.activity_log_path',
            'simola/errorlog-monthly/activity.jsonl'
        ));

        return ltrim($path !== '' ? $path : 'simola/errorlog-monthly/activity.jsonl', '/\\');
    }

    private function compact(string $value, int $max): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        if (mb_strlen($value, 'UTF-8') <= $max) {
            return $value;
        }

        return mb_substr($value, 0, max(0, $max - 3), 'UTF-8') . '...';
    }
}
