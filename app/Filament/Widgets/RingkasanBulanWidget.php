<?php

namespace App\Filament\Widgets;

use App\Models\PengisianKasKecil;
use App\Services\Reports\CashFlowReportService;
use App\Services\Reports\SppArrearsReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class RingkasanBulanWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = null;

    protected function getStats(): array
    {
        $bulan = now()->month;
        $tahun = now()->year;
        $isIntegrationEnabled = (bool) config('spp_integration.enabled');

        $cashFlow = app(CashFlowReportService::class)->build($bulan, $tahun);
        $arrears = app(SppArrearsReportService::class)->build($bulan, $tahun);

        $totalMasuk = (float) ($cashFlow['total_masuk'] ?? 0);
        $totalKeluarBesar = (float) ($cashFlow['total_keluar_besar'] ?? 0);
        $totalKasKecil = (float) ($cashFlow['total_kas_kecil'] ?? 0);
        $saldoBersih = (float) ($cashFlow['selisih'] ?? 0);
        $totalSiswaAktif = (int) ($arrears['total_siswa_aktif'] ?? 0);
        $sudahBayar = (int) ($arrears['total_sudah_bayar'] ?? 0);
        $belumBayar = (int) ($arrears['total_belum_bayar'] ?? 0);

        $pengisian = PengisianKasKecil::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sum('nominal');
        $saldoKasKecil = (float) ($cashFlow['saldo_kas_kecil'] ?? ($pengisian - $totalKasKecil));
        $sppDescription = $isIntegrationEnabled
            ? 'Data SmartSIS bulan ' . now()->translatedFormat('F Y')
            : 'Bulan ' . now()->translatedFormat('F Y');
        $belumBayarDescription = $isIntegrationEnabled
            ? 'SmartSIS: dari total ' . $totalSiswaAktif . ' siswa aktif'
            : 'Dari total ' . $totalSiswaAktif . ' siswa aktif';

        return [
            Stat::make('Total Penerimaan ' . now()->format('M Y'), 'Rp ' . Number::format($totalMasuk, 0))
                ->description('Cash + Bank masuk bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pengeluaran', 'Rp ' . Number::format($totalKeluarBesar + $totalKasKecil, 0))
                ->description('Besar: Rp ' . Number::format($totalKeluarBesar, 0) . ' | Kas Kecil: Rp ' . Number::format($totalKasKecil, 0))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Saldo Bersih Bulan Ini', 'Rp ' . Number::format($saldoBersih, 0))
                ->description('Penerimaan - Pengeluaran')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldoBersih >= 0 ? 'success' : 'danger'),

            Stat::make('Saldo Kas Kecil', 'Rp ' . Number::format($saldoKasKecil, 0))
                ->description('Pengisian: Rp ' . Number::format($pengisian, 0) . ' | Keluar: Rp ' . Number::format($totalKasKecil, 0))
                ->descriptionIcon('heroicon-m-wallet')
                ->color($saldoKasKecil >= 0 ? 'info' : 'warning'),

            Stat::make('Siswa Belum Bayar SPP', $belumBayar . ' siswa')
                ->description($belumBayarDescription)
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($belumBayar === 0 ? 'success' : 'warning'),

            Stat::make('SPP Sudah Terbayar', $sudahBayar . ' siswa')
                ->description($sppDescription)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
