<?php

namespace App\Jobs;

use App\Models\SmartsisSyncRun;
use App\Models\User;
use App\Services\Integrations\SmartsisFullSyncService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncSmartsisYearToDateJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public int $syncRunId,
    ) {
        $this->onQueue('default');
    }

    public function handle(SmartsisFullSyncService $syncService): void
    {
        $syncRun = SmartsisSyncRun::query()->find($this->syncRunId);

        if (! $syncRun) {
            return;
        }

        $syncRun->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null,
            'error_message' => null,
        ])->save();

        $result = $syncService->syncYearToDate($syncRun->tahun, $syncRun->requested_by);
        $warnings = array_merge(
            $result['master_errors'] ?? [],
            $result['arrears_errors'] ?? [],
            $result['spp']['errors'] ?? [],
        );

        $syncRun->forceFill([
            'status' => 'done',
            'finished_at' => now(),
            'months_synced' => $result['months'] ?? [],
            'result_summary' => [
                'payments_fetched' => (int) ($result['spp']['fetched'] ?? 0),
                'students_fetched' => (int) ($result['master']['students_fetched'] ?? 0),
                'arrears_synced_months' => count($result['arrears'] ?? []),
                'warning_count' => count($warnings),
            ],
            'error_message' => $warnings !== []
                ? implode(' ', array_slice($warnings, 0, 3))
                : null,
        ])->save();

        $user = User::query()->find($syncRun->requested_by);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Sync SmartSIS selesai')
            ->body(sprintf(
                'Tahun %d, bulan %s. Pembayaran %d data, siswa aktif %d, tunggakan %d bulan.',
                $syncRun->tahun,
                implode(', ', $result['months'] ?: ['-']),
                $result['spp']['fetched'] ?? 0,
                $result['master']['students_fetched'] ?? 0,
                count($result['arrears'] ?? []),
            ))
            ->success()
            ->sendToDatabase($user);

        if ($warnings !== []) {
            Notification::make()
                ->title('Sync SmartSIS selesai dengan catatan')
                ->body(implode(' ', array_slice($warnings, 0, 3)))
                ->warning()
                ->sendToDatabase($user);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $syncRun = SmartsisSyncRun::query()->find($this->syncRunId);

        if ($syncRun) {
            $syncRun->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception?->getMessage() ?: 'Terjadi kesalahan saat menjalankan sync di background.',
            ])->save();
        }

        $user = User::query()->find($syncRun?->requested_by);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Sync SmartSIS gagal')
            ->body($exception?->getMessage() ?: 'Terjadi kesalahan saat menjalankan sync di background.')
            ->danger()
            ->sendToDatabase($user);
    }
}
