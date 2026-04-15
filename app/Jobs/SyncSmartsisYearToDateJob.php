<?php

namespace App\Jobs;

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
        public int $tahun,
        public int $actorId,
    ) {
        $this->onQueue('default');
    }

    public function handle(SmartsisFullSyncService $syncService): void
    {
        $result = $syncService->syncYearToDate($this->tahun, $this->actorId);
        $warnings = array_merge(
            $result['master_errors'] ?? [],
            $result['arrears_errors'] ?? [],
            $result['spp']['errors'] ?? [],
        );

        $user = User::query()->find($this->actorId);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Sync SmartSIS selesai')
            ->body(sprintf(
                'Tahun %d, bulan %s. Pembayaran %d data, siswa aktif %d, tunggakan %d bulan.',
                $this->tahun,
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
        $user = User::query()->find($this->actorId);

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
