<?php

namespace App\Services\Integrations;

use Throwable;

class SmartsisFullSyncService
{
    public function __construct(
        protected SmartsisSppClient $client,
        protected SmartsisSppSyncService $sppSyncService,
        protected SmartsisReferenceSyncService $referenceSyncService,
    ) {
    }

    public function syncYearToDate(int $tahun, int $actorId): array
    {
        $months = $this->resolveMonths($tahun);

        if ($months === []) {
            return [
                'tahun' => $tahun,
                'months' => [],
                'master' => null,
                'arrears' => [],
                'spp' => [
                    'fetched' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'deleted' => 0,
                    'errors' => [],
                ],
            ];
        }

        $masterErrors = [];
        $master = null;
        $snapshot = null;

        try {
            $snapshot = $this->client->getYearToDateSnapshot($tahun);
        } catch (Throwable $exception) {
            $masterErrors[] = 'Snapshot tahunan tidak tersedia, fallback ke mode bertahap: ' . $exception->getMessage();
        }

        try {
            $master = $snapshot
                ? $this->referenceSyncService->syncMasterDataFromRows($snapshot['master']['students'] ?? [])
                : $this->referenceSyncService->syncMasterData();
        } catch (Throwable $exception) {
            $masterErrors[] = 'Sync master gagal: ' . $exception->getMessage();
            $master = [
                'students_fetched' => 0,
                'jurusan_created' => 0,
                'jurusan_updated' => 0,
                'kelas_created' => 0,
                'kelas_updated' => 0,
                'siswa_created' => 0,
                'siswa_updated' => 0,
                'siswa_deactivated' => 0,
            ];
        }

        $spp = [
            'fetched' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0,
            'errors' => [],
            'months' => [],
        ];
        $arrears = [];
        $arrearsErrors = [];

        foreach ($months as $bulan) {
            try {
                $sppMonth = $snapshot
                    ? $this->sppSyncService->syncMonthFromRows(
                        $snapshot['payments']['by_month'][$bulan] ?? [],
                        $bulan,
                        $tahun,
                        $actorId,
                    )
                    : $this->sppSyncService->syncMonth($bulan, $tahun, $actorId);
            } catch (Throwable $exception) {
                $sppMonth = [
                    'fetched' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'deleted' => 0,
                    'errors' => ['Sync pembayaran bulan ' . $bulan . ' gagal: ' . $exception->getMessage()],
                ];
            }

            try {
                $arrears[$bulan] = $snapshot
                    ? $this->referenceSyncService->syncArrearsFromReport(
                        $snapshot['arrears']['by_month'][$bulan] ?? [
                            'rows' => [],
                            'total_siswa_aktif' => 0,
                            'total_belum_bayar' => 0,
                        ],
                        $bulan,
                        $tahun,
                    )
                    : $this->referenceSyncService->syncArrears($bulan, $tahun);
            } catch (Throwable $exception) {
                $arrearsErrors[] = 'Sync tunggakan bulan ' . $bulan . ' gagal: ' . $exception->getMessage();
                $arrears[$bulan] = [
                    'rows_fetched' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'deleted' => 0,
                    'total_siswa_aktif' => 0,
                    'total_belum_bayar' => 0,
                    'error' => $exception->getMessage(),
                ];
            }

            $spp['months'][$bulan] = $sppMonth;
            $spp['fetched'] += (int) ($sppMonth['fetched'] ?? 0);
            $spp['created'] += (int) ($sppMonth['created'] ?? 0);
            $spp['updated'] += (int) ($sppMonth['updated'] ?? 0);
            $spp['skipped'] += (int) ($sppMonth['skipped'] ?? 0);
            $spp['deleted'] += (int) ($sppMonth['deleted'] ?? 0);
            $spp['errors'] = array_merge($spp['errors'], $sppMonth['errors'] ?? []);
        }

        $spp['errors'] = array_values(array_unique($spp['errors']));

        return [
            'tahun' => $tahun,
            'months' => $months,
            'master' => $master,
            'source' => $snapshot ? 'snapshot' : 'fallback',
            'master_errors' => array_values(array_unique($masterErrors)),
            'arrears' => $arrears,
            'arrears_errors' => array_values(array_unique($arrearsErrors)),
            'spp' => $spp,
        ];
    }

    protected function resolveMonths(int $tahun): array
    {
        $currentYear = now()->year;
        $lastMonth = match (true) {
            $tahun < $currentYear => 12,
            $tahun === $currentYear => now()->month,
            default => 0,
        };

        return $lastMonth > 0 ? range(1, $lastMonth) : [];
    }
}
