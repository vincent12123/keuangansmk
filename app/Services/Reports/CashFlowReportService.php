<?php

namespace App\Services\Reports;

use App\Models\JurnalKas;
use App\Models\KasKecil;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashFlowReportService
{
    public function build(int $bulan, int $tahun): array
    {
        $opening = app(SaldoKasService::class)->getOpeningBalance($bulan, $tahun);
        $externalSpp = app(ExternalSppReportService::class)->getMonthlySummary($bulan, $tahun);
        $useExternalOverlay = in_array($externalSpp['source'] ?? null, ['remote', 'cache'], true);
        $movements = $this->getMovementTotals($bulan, $tahun, $externalSpp, $useExternalOverlay);
        $penerimaanSections = $this->getIncomingSections($bulan, $tahun, $externalSpp, $useExternalOverlay);
        $pengeluaranSections = $this->getExpenseSections($bulan, $tahun);
        $totalPenerimaan = (float) collect($penerimaanSections)->sum('total');
        $totalPengeluaran = (float) collect($pengeluaranSections)->sum('total');
        $selisih = $totalPenerimaan - $totalPengeluaran;
        $saldoAkhirTotal = ($opening['cash'] + $opening['bank']) + $selisih;
        $saldoKasKecil = $movements['pengisian_kas_kecil'] - $movements['kas_kecil'];
        $saldoKasBesar = $saldoAkhirTotal - $saldoKasKecil;

        // Cash/bank split tetap dipertahankan untuk carry saldo awal bulan berikutnya.
        $saldoAkhirCash = $opening['cash'] + $movements['masuk_cash'] - $movements['keluar_cash'] - $movements['kas_kecil'];
        $saldoAkhirBank = $opening['bank'] + $movements['masuk_bank'] - $movements['keluar_bank'];

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'is_locked' => $opening['is_locked'],
            'opening_source' => $opening['source'],
            'saldo_awal_cash' => $opening['cash'],
            'saldo_awal_bank' => $opening['bank'],
            'saldo_awal_total' => $opening['cash'] + $opening['bank'],
            'total_masuk_cash' => $movements['masuk_cash'],
            'total_masuk_bank' => $movements['masuk_bank'],
            'total_masuk' => $totalPenerimaan,
            'total_keluar_besar_cash' => $movements['keluar_cash'],
            'total_keluar_besar_bank' => $movements['keluar_bank'],
            'total_keluar_besar' => $movements['keluar_cash'] + $movements['keluar_bank'],
            'total_kas_kecil' => $movements['kas_kecil'],
            'total_pengisian_kas_kecil' => $movements['pengisian_kas_kecil'],
            'total_pengeluaran' => $totalPengeluaran,
            'selisih' => $selisih,
            'saldo_akhir_cash' => $saldoAkhirCash,
            'saldo_akhir_bank' => $saldoAkhirBank,
            'saldo_akhir_total' => $saldoAkhirTotal,
            'saldo_kas_kecil' => $saldoKasKecil,
            'saldo_kas_besar' => $saldoKasBesar,
            'penerimaan_sections' => $penerimaanSections,
            'pengeluaran_sections' => $pengeluaranSections,
            'external_spp' => $externalSpp,
        ];
    }

    protected function getMovementTotals(int $bulan, int $tahun, array $externalSpp, bool $useExternalOverlay): array
    {
        $masuk = JurnalKas::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'masuk')
            ->when($useExternalOverlay, function ($query) {
                $query->whereDoesntHave('kodeAkun', function ($builder) {
                    $builder->whereIn('kode', JurnalKas::SPP_ACCOUNT_CODES);
                });
            })
            ->selectRaw('COALESCE(SUM(cash), 0) as cash_total, COALESCE(SUM(bank), 0) as bank_total')
            ->first();

        $keluar = JurnalKas::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'keluar')
            ->selectRaw('COALESCE(SUM(cash), 0) as cash_total, COALESCE(SUM(bank), 0) as bank_total')
            ->first();

        return [
            'masuk_cash' => (float) ($masuk->cash_total ?? 0) + ($useExternalOverlay ? (float) ($externalSpp['total_cash'] ?? 0) : 0.0),
            'masuk_bank' => (float) ($masuk->bank_total ?? 0) + ($useExternalOverlay ? (float) ($externalSpp['total_bank'] ?? 0) : 0.0),
            'keluar_cash' => (float) ($keluar->cash_total ?? 0),
            'keluar_bank' => (float) ($keluar->bank_total ?? 0),
            'kas_kecil' => (float) KasKecil::query()
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->sum('nominal'),
            'pengisian_kas_kecil' => (float) DB::table('pengisian_kas_kecil')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->sum('nominal'),
        ];
    }

    protected function getIncomingSections(int $bulan, int $tahun, array $externalSpp, bool $useExternalOverlay): array
    {
        $rows = JurnalKas::query()
            ->select([
                'kode_akun_id',
                DB::raw('SUM(cash) as total_cash'),
                DB::raw('SUM(bank) as total_bank'),
                DB::raw('SUM(cash + bank) as total_nominal'),
            ])
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'masuk')
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get()
            ->map(function (JurnalKas $row): array {
                return [
                    'kategori' => $row->kodeAkun?->kategori,
                    'kode' => $row->kodeAkun?->kode ?? '-',
                    'nama' => $row->kodeAkun?->nama ?? '-',
                    'cash' => (float) $row->total_cash,
                    'bank' => (float) $row->total_bank,
                    'total' => (float) $row->total_nominal,
                ];
            });

        if ($useExternalOverlay) {
            $rows = $rows
                ->reject(fn (array $row) => in_array($row['kode'], JurnalKas::SPP_ACCOUNT_CODES, true))
                ->values()
                ->concat(collect($externalSpp['rows'] ?? [])->map(fn (array $row): array => [
                    'kategori' => 'PENERIMAAN PENDIDIKAN',
                    'kode' => $row['kode'],
                    'nama' => $row['nama'],
                    'cash' => (float) $row['cash'],
                    'bank' => (float) $row['bank'],
                    'total' => (float) $row['total'],
                ]));
        }

        $sections = [
            'B1' => ['title' => 'Penerimaan Pendidikan', 'kategori' => 'PENERIMAAN PENDIDIKAN'],
            'B2' => ['title' => 'Penerimaan Non Pendidikan', 'kategori' => 'PENDAPATAN NON PENDIDIKAN'],
            'B3' => ['title' => 'Pinjaman', 'kategori' => 'PINJAMAN'],
        ];

        return collect($sections)->map(function (array $section) use ($rows): array {
            $items = $rows
                ->where('kategori', $section['kategori'])
                ->sortBy('kode')
                ->values();

            return [
                'title' => $section['title'],
                'rows' => $items,
                'total' => (float) $items->sum('total'),
            ];
        })->all();
    }

    protected function getExpenseSections(int $bulan, int $tahun): array
    {
        $fromJurnal = JurnalKas::query()
            ->select([
                'kode_akun_id',
                DB::raw('SUM(cash + bank) as total_nominal'),
            ])
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('jenis', 'keluar')
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get()
            ->mapWithKeys(fn (JurnalKas $row) => [
                $row->kodeAkun?->kode => [
                    'kode' => $row->kodeAkun?->kode ?? '-',
                    'nama' => $row->kodeAkun?->nama ?? '-',
                    'total' => (float) $row->total_nominal,
                ],
            ]);

        $fromKasKecil = KasKecil::query()
            ->select([
                'kode_akun_id',
                DB::raw('SUM(nominal) as total_nominal'),
            ])
            ->with('kodeAkun')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->groupBy('kode_akun_id')
            ->orderBy('kode_akun_id')
            ->get();

        foreach ($fromKasKecil as $row) {
            $kode = $row->kodeAkun?->kode;

            if (! $kode) {
                continue;
            }

            $fromJurnal[$kode] = [
                'kode' => $kode,
                'nama' => $row->kodeAkun?->nama ?? '-',
                'total' => (float) ($fromJurnal[$kode]['total'] ?? 0) + (float) $row->total_nominal,
            ];
        }

        $sections = [
            'C1' => 'Gaji dan Tunjangan',
            'C2' => 'Beban Pegawai Lainnya',
            'C3' => 'Beban Operasional Kantor',
            'C4' => 'Beban Pemasaran',
            'C5' => 'Kontrak Pelayanan',
            'C6' => 'Asuransi',
            'C7' => 'Pengadaan Fasilitas',
            'C8' => 'Kegiatan Siswa',
            'C9' => 'Kegiatan Sosial',
            'C10' => 'Perjalanan Dinas',
            'C11' => 'Pendidikan dan Latihan',
            'C12' => 'Biaya Lain-lain',
        ];

        $grouped = [];

        foreach ($sections as $key => $title) {
            $grouped[$key] = [
                'title' => $title,
                'rows' => collect(),
                'total' => 0.0,
            ];
        }

        foreach (collect($fromJurnal)->sortBy('kode') as $row) {
            $sectionKey = $this->resolveExpenseSection($row['kode']);

            if (! $sectionKey) {
                continue;
            }

            $grouped[$sectionKey]['rows']->push($row);
            $grouped[$sectionKey]['total'] += (float) $row['total'];
        }

        foreach ($grouped as &$section) {
            $section['rows'] = $section['rows']->values();
        }

        return $grouped;
    }

    protected function resolveExpenseSection(string $kode): ?string
    {
        return match (true) {
            str_starts_with($kode, '5.01.01'), str_starts_with($kode, '5.01.02') => 'C1',
            str_starts_with($kode, '5.01.03') => 'C2',
            str_starts_with($kode, '5.02') => 'C3',
            str_starts_with($kode, '5.03') => 'C4',
            str_starts_with($kode, '5.04') => 'C5',
            str_starts_with($kode, '5.05') => 'C6',
            str_starts_with($kode, '5.06') => 'C7',
            str_starts_with($kode, '5.07') => 'C8',
            str_starts_with($kode, '5.08') => 'C9',
            str_starts_with($kode, '5.09') => 'C10',
            str_starts_with($kode, '5.10') => 'C11',
            str_starts_with($kode, '5.11') => 'C12',
            default => null,
        };
    }
}
