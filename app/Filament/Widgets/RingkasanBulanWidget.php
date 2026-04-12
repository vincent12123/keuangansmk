<?php

namespace App\Filament\Widgets;

use App\Models\JurnalKas;
use App\Models\KartuSpp;
use App\Models\KasKecil;
use App\Models\PengisianKasKecil;
use App\Models\Siswa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class RingkasanBulanWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = null;

    protected function getStats(): array
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $totalMasuk = JurnalKas::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'masuk')
            ->sum(DB::raw('cash + bank'));

        $totalKeluarBesar = JurnalKas::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'keluar')
            ->sum(DB::raw('cash + bank'));

        $totalKasKecil = KasKecil::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sum('nominal');

        $saldoBersih = $totalMasuk - $totalKeluarBesar - $totalKasKecil;

        $totalSiswaAktif = Siswa::aktif()->count();
        $sudahBayar = KartuSpp::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->distinct()
            ->count('nis');
        $belumBayar = $totalSiswaAktif - $sudahBayar;

        $pengisian = PengisianKasKecil::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sum('nominal');
        $saldoKasKecil = $pengisian - $totalKasKecil;

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
                ->description('Dari total ' . $totalSiswaAktif . ' siswa aktif')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($belumBayar === 0 ? 'success' : 'warning'),

            Stat::make('SPP Sudah Terbayar', $sudahBayar . ' siswa')
                ->description('Bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
