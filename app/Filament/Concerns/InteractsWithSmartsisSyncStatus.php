<?php

namespace App\Filament\Concerns;

use App\Jobs\SyncSmartsisYearToDateJob;
use App\Models\SmartsisSyncRun;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;

trait InteractsWithSmartsisSyncStatus
{
    protected function queueSmartsisSyncForSelectedYear(): void
    {
        $tahun = $this->getSmartsisSyncYear();
        $existingRun = SmartsisSyncRun::query()
            ->where('sync_type', 'year_to_date')
            ->where('tahun', $tahun)
            ->active()
            ->latest('id')
            ->first();

        if ($existingRun) {
            Notification::make()
                ->title('Sync SmartSIS masih berjalan')
                ->body('Masih ada proses sync tahun ' . $tahun . ' dengan status ' . $this->humanizeSyncStatus($existingRun->status) . '.')
                ->warning()
                ->send();

            return;
        }

        $run = SmartsisSyncRun::query()->create([
            'sync_type' => 'year_to_date',
            'tahun' => $tahun,
            'status' => 'queued',
            'requested_by' => auth()->id(),
        ]);

        SyncSmartsisYearToDateJob::dispatch($run->id);

        Notification::make()
            ->title('Sync SmartSIS dimulai')
            ->body('Proses sinkronisasi berjalan di background. Statusnya bisa dilihat di panel kecil halaman ini.')
            ->success()
            ->send();
    }

    #[Computed]
    public function latestSmartsisSyncStatus(): ?array
    {
        if (! config('spp_integration.enabled')) {
            return null;
        }

        $run = SmartsisSyncRun::query()
            ->latestForYear($this->getSmartsisSyncYear())
            ->first();

        if (! $run) {
            return null;
        }

        $summary = $run->result_summary ?? [];
        $requestedAt = $run->created_at;
        $finishedAt = $run->finished_at;

        return [
            'id' => $run->id,
            'tahun' => $run->tahun,
            'status' => $run->status,
            'status_label' => $this->humanizeSyncStatus($run->status),
            'tone' => match ($run->status) {
                'queued' => 'amber',
                'running' => 'sky',
                'done' => 'emerald',
                'failed' => 'rose',
                default => 'slate',
            },
            'requested_at' => $requestedAt?->format('d M Y H:i'),
            'started_at' => $run->started_at?->format('d M Y H:i'),
            'finished_at' => $finishedAt?->format('d M Y H:i'),
            'requested_by' => $run->requester?->name,
            'months_count' => count($run->months_synced ?? []),
            'payments_fetched' => (int) ($summary['payments_fetched'] ?? 0),
            'students_fetched' => (int) ($summary['students_fetched'] ?? 0),
            'arrears_synced_months' => (int) ($summary['arrears_synced_months'] ?? 0),
            'warning_count' => (int) ($summary['warning_count'] ?? 0),
            'error_message' => $run->error_message,
        ];
    }

    protected function getSmartsisSyncYear(): int
    {
        return (int) $this->tahun;
    }

    protected function humanizeSyncStatus(string $status): string
    {
        return match ($status) {
            'queued' => 'Queued',
            'running' => 'Running',
            'done' => 'Done',
            'failed' => 'Failed',
            default => ucfirst($status),
        };
    }
}
