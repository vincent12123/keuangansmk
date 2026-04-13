<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 20px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        h2, h3 { text-align: center; margin: 0; }
        h2 { font-size: 13px; margin-bottom: 4px; }
        h3 { font-size: 11px; font-weight: normal; margin-bottom: 12px; }
        .tbl td, .tbl th { border: 1px solid #bbb; padding: 4px 6px; }
        .tbl th { background: #dce6f1; }
        .section { background: #eef3f8; font-weight: bold; }
        .subtotal { font-weight: bold; background: #f7f7f7; }
        .grand { font-weight: bold; background: #e2e8f0; }
        .right { text-align: right; }
        .code { font-family: monospace; width: 90px; }
        .ttd { width: 100%; margin-top: 28px; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .line { display: inline-block; min-width: 180px; border-top: 1px solid #000; margin-top: 52px; }
    </style>
</head>
<body>
    @include('pdf.partials.kop-surat')

    <h2>LAPORAN PENERIMAAN DAN PENGELUARAN</h2>
    <h3>BULAN : {{ strtoupper($namaBulan) }} {{ $tahun }}</h3>

    <table class="tbl">
        <tr class="section">
            <td style="width:60px;">A</td>
            <td colspan="2">SALDO AWAL OPERASIONAL</td>
            <td class="right" style="width:140px;">{{ number_format($report['saldo_awal_total'], 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="4" style="border:none; height:8px;"></td></tr>
        <tr class="section"><td>B</td><td colspan="3">PENERIMAAN</td></tr>
        @foreach ($report['penerimaan_sections'] as $sectionKey => $section)
            <tr class="subtotal">
                <td>{{ $sectionKey }}</td>
                <td colspan="2">{{ $section['title'] }}</td>
                <td class="right">{{ number_format($section['total'], 0, ',', '.') }}</td>
            </tr>
            @foreach ($section['rows'] as $row)
                <tr>
                    <td></td>
                    <td class="code">{{ $row['kode'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td class="right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="grand">
            <td>B</td>
            <td colspan="2">TOTAL SELURUH PENERIMAAN</td>
            <td class="right">{{ number_format($report['total_masuk'], 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="4" style="border:none; height:8px;"></td></tr>
        <tr class="section"><td>C</td><td colspan="3">PENGELUARAN</td></tr>
        @foreach ($report['pengeluaran_sections'] as $sectionKey => $section)
            <tr class="subtotal">
                <td>{{ $sectionKey }}</td>
                <td colspan="2">{{ $section['title'] }}</td>
                <td class="right">{{ number_format($section['total'], 0, ',', '.') }}</td>
            </tr>
            @foreach ($section['rows'] as $row)
                <tr>
                    <td></td>
                    <td class="code">{{ $row['kode'] }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td class="right">{{ number_format($row['total'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="grand">
            <td>C</td>
            <td colspan="2">JUMLAH PENGELUARAN</td>
            <td class="right">{{ number_format($report['total_pengeluaran'], 0, ',', '.') }}</td>
        </tr>
        <tr class="subtotal">
            <td></td>
            <td colspan="2">SELISIH PENERIMAAN DENGAN PENGELUARAN</td>
            <td class="right">{{ number_format($report['selisih'], 0, ',', '.') }}</td>
        </tr>

        <tr><td colspan="4" style="border:none; height:8px;"></td></tr>
        <tr class="section">
            <td>D</td>
            <td colspan="2">SALDO AKHIR OPERASIONAL</td>
            <td class="right">{{ number_format($report['saldo_akhir_total'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Kas Kecil</td>
            <td class="right">{{ number_format($report['saldo_kas_kecil'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td></td>
            <td colspan="2">Kas Besar</td>
            <td class="right">{{ number_format($report['saldo_kas_besar'], 0, ',', '.') }}</td>
        </tr>
        <tr class="grand">
            <td></td>
            <td colspan="2">Jumlah Saldo Kas</td>
            <td class="right">{{ number_format($report['saldo_akhir_total'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="ttd">
        <tr>
            <td>
                Mengetahui,<br>Kepala Sekolah
                <div class="line">{{ $namaKepalaSekolah }}</div>
            </td>
            <td>
                Sintang, {{ now()->format('d/m/Y') }}<br>Bendahara
                <div class="line">{{ $namaBendahara }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
