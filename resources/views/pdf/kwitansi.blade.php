<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        table { width: 100%; border-collapse: collapse; }
        .title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 12px; }
        .meta td { padding: 4px 0; vertical-align: top; }
        .amount-box { border: 2px solid #000; padding: 12px; margin: 18px 0; text-align: center; }
        .amount-main { font-size: 22px; font-weight: bold; }
        .ttd td { width: 50%; text-align: center; padding-top: 36px; }
        .name-line { margin-top: 60px; display: inline-block; min-width: 180px; border-top: 1px solid #000; }
    </style>
</head>
<body>
    @include('pdf.partials.kop-surat')

    <div class="title">KWITANSI PEMBAYARAN</div>

    <table class="meta">
        <tr>
            <td style="width: 160px;">No. Kwitansi</td>
            <td style="width: 16px;">:</td>
            <td>{{ $transaksi->no_kwitansi ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $transaksi->tanggal?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Sudah diterima dari</td>
            <td>:</td>
            <td>{{ $transaksi->nama_penyetor }}</td>
        </tr>
        <tr>
            <td>NIS / Kelas</td>
            <td>:</td>
            <td>{{ $transaksi->nis ?? '-' }}{{ $transaksi->kelas?->nama_kelas ? ' / ' . $transaksi->kelas->nama_kelas : '' }}</td>
        </tr>
        <tr>
            <td>Untuk pembayaran</td>
            <td>:</td>
            <td>{{ $transaksi->uraian }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div>Jumlah Pembayaran</div>
        <div class="amount-main">Rp {{ number_format($transaksi->cash + $transaksi->bank, 0, ',', '.') }}</div>
        <div style="margin-top: 8px; font-style: italic;">{{ $terbilang }}</div>
    </div>

    @if ($transaksi->cash > 0 && $transaksi->bank > 0)
        <div style="margin-bottom: 12px; color: #444;">
            Cash: Rp {{ number_format($transaksi->cash, 0, ',', '.') }} |
            Bank: Rp {{ number_format($transaksi->bank, 0, ',', '.') }}
        </div>
    @endif

    <table class="ttd">
        <tr>
            <td>
                Penyetor<br>
                <span class="name-line">{{ $transaksi->nama_penyetor }}</span>
            </td>
            <td>
                Bendahara<br>
                <span class="name-line">{{ $namaBendahara }}</span>
            </td>
        </tr>
    </table>
</body>
</html>
