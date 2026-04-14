<?php

namespace App\Http\Controllers;

use App\Models\JurnalKas;
use App\Services\AuditTrailService;
use App\Services\ExportPdfService;
use App\Services\Reports\CashFlowReportService;
use App\Services\Reports\PettyCashReportService;
use App\Services\TerbilangService;
use App\Support\ReportHelper;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function kwitansi(JurnalKas $jurnalKas)
    {
        abort_unless(auth()->user()?->can('view', $jurnalKas), 403);
        abort_unless($jurnalKas->jenis === 'masuk', 404);

        app(AuditTrailService::class)->logPrint('kwitansi_pdf', [
            'jurnal_kas_id' => $jurnalKas->id,
            'no_kwitansi' => $jurnalKas->no_kwitansi,
            'tanggal' => optional($jurnalKas->tanggal)->toDateString(),
        ]);

        return app(ExportPdfService::class)->stream(
            'pdf.kwitansi',
            [
                'transaksi' => $jurnalKas->load(['kodeAkun', 'kelas']),
                'terbilang' => app(TerbilangService::class)->convert((float) $jurnalKas->cash + (float) $jurnalKas->bank),
                'namaBendahara' => auth()->user()?->name ?? 'Bendahara',
            ],
            'kwitansi-' . ($jurnalKas->no_kwitansi ?: $jurnalKas->id) . '.pdf',
        );
    }

    public function arusKas(Request $request)
    {
        abort_unless(auth()->user()?->hasPermissionTo('view_laporan_arus_kas'), 403);

        $bulan = (int) $request->integer('bulan', now()->month);
        $tahun = (int) $request->integer('tahun', now()->year);

        app(AuditTrailService::class)->logPrint('arus_kas_pdf', [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);

        return app(ExportPdfService::class)->stream(
            'pdf.arus-kas-bulanan',
            [
                'report' => app(CashFlowReportService::class)->build($bulan, $tahun),
                'namaBulan' => ReportHelper::monthName($bulan),
                'tahun' => $tahun,
                'namaBendahara' => 'Bendahara SMK',
                'namaKepalaSekolah' => 'Kepala Sekolah',
            ],
            'Arus-Kas-' . ReportHelper::monthName($bulan) . '-' . $tahun . '.pdf',
        );
    }

    public function rekapKasKecil(Request $request)
    {
        abort_unless(auth()->user()?->hasPermissionTo('view_laporan_kas_kecil'), 403);

        $bulan = (int) $request->integer('bulan', now()->month);
        $tahun = (int) $request->integer('tahun', now()->year);

        app(AuditTrailService::class)->logPrint('rekap_kas_kecil_pdf', [
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);

        return app(ExportPdfService::class)->stream(
            'pdf.rekap-kas-kecil',
            [
                'report' => app(PettyCashReportService::class)->build($bulan, $tahun),
                'namaBulan' => ReportHelper::monthName($bulan),
                'tahun' => $tahun,
                'namaBendahara' => 'Bendahara SMK',
                'namaKepalaSekolah' => 'Kepala Sekolah',
            ],
            'Rekap-Kas-Kecil-' . ReportHelper::monthName($bulan) . '-' . $tahun . '.pdf',
        );
    }
}
