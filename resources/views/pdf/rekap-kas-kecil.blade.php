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
        .right { text-align: right; }
        .ttd { width: 100%; margin-top: 28px; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd-meta { margin-bottom: 10px; }
        .ttd-role { margin-bottom: 52px; }
        .ttd-name { display: inline-block; min-width: 180px; border-top: 1px solid #000; padding-top: 6px; }
    </style>
</head>
<body>
    @include('pdf.partials.kop-surat')

    <h2>REKAP KAS KECIL</h2>
    <h3>BULAN : {{ strtoupper($namaBulan) }} {{ $tahun }}</h3>

    <table class="tbl" style="margin-bottom: 12px;">
        <tr>
            <th>Total Pengisian</th>
            <th>Total Pengeluaran</th>
            <th>Saldo</th>
            <th>Validasi ke Arus Kas</th>
        </tr>
        <tr>
            <td class="right">{{ number_format($report['total_pengisian'], 0, ',', '.') }}</td>
            <td class="right">{{ number_format($report['total_pengeluaran'], 0, ',', '.') }}</td>
            <td class="right">{{ number_format($report['saldo'], 0, ',', '.') }}</td>
            <td class="right">{{ number_format($report['validation_diff'], 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="tbl">
        <thead>
            <tr>
                <th style="width: 42px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 90px;">Kode</th>
                <th>Uraian</th>
                <th style="width: 90px;">No Ref</th>
                <th style="width: 110px;">Kredit</th>
                <th style="width: 110px;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['transactions'] as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['tanggal']?->format('d/m/Y') }}</td>
                    <td>{{ $row['kode'] }}</td>
                    <td>{{ $row['uraian'] }}</td>
                    <td>{{ $row['no_ref'] }}</td>
                    <td class="right">{{ number_format($row['nominal'], 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($row['saldo'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td>
                <div class="ttd-meta">Mengetahui,</div>
                <div class="ttd-role">Kepala Sekolah</div>
                <div class="ttd-name">{{ $namaKepalaSekolah }}</div>
            </td>
            <td>
                <div class="ttd-meta">Sintang, {{ now()->format('d/m/Y') }}</div>
                <div class="ttd-role">Bendahara</div>
                <div class="ttd-name">{{ $namaBendahara }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
