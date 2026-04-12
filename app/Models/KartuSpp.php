<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ─── KartuSpp ────────────────────────────────────────────────
class KartuSpp extends Model
{
    protected $table = 'kartu_spp';

    protected $fillable = [
        'nis', 'bulan', 'tahun', 'nominal',
        'tgl_bayar', 'jurnal_kas_id', 'keterangan',
    ];

    protected $casts = [
        'tgl_bayar' => 'date',
        'nominal'   => 'decimal:0',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'nis', 'nis');
    }

    public function jurnalKas(): BelongsTo
    {
        return $this->belongsTo(JurnalKas::class);
    }

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function getNamaBulanAttribute(): string
    {
        return [
            1=>'Januari', 2=>'Februari', 3=>'Maret',
            4=>'April',   5=>'Mei',       6=>'Juni',
            7=>'Juli',    8=>'Agustus',   9=>'September',
            10=>'Oktober',11=>'November', 12=>'Desember',
        ][$this->bulan] ?? (string) $this->bulan;
    }
}
