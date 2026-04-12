<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use SoftDeletes;

    protected $table = 'siswa';

    protected $fillable = [
        'nis', 'nama', 'kelas_id', 'jurusan_id',
        'angkatan', 'nominal_spp', 'status',
        'no_hp_wali', 'nama_wali',
    ];

    protected $casts = [
        'angkatan'    => 'integer',
        'nominal_spp' => 'decimal:0',
    ];

    // ─── Relations ───────────────────────────────────────────
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    public function kartuSpp(): HasMany
    {
        return $this->hasMany(KartuSpp::class, 'nis', 'nis');
    }

    // ─── Scopes ──────────────────────────────────────────────
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    // ─── Helpers ─────────────────────────────────────────────

    // Ambil daftar bulan yang sudah dibayar SPP tahun ini
    public function bulanSudahBayar(int $tahun): array
    {
        return $this->kartuSpp()
            ->where('tahun', $tahun)
            ->pluck('bulan')
            ->toArray();
    }

    // Hitung tunggakan: berapa bulan belum bayar sampai bulan ini
    public function hitungTunggakan(int $bulanSekarang, int $tahun): int
    {
        $sudahBayar = $this->bulanSudahBayar($tahun);
        $tunggakan  = 0;
        for ($b = 1; $b <= $bulanSekarang; $b++) {
            if (! in_array($b, $sudahBayar)) {
                $tunggakan++;
            }
        }
        return $tunggakan;
    }

    public function getTotalTunggakanAttribute(): int
    {
        $bulanIni = (int) now()->format('m');
        $tahun    = (int) now()->format('Y');
        return $this->hitungTunggakan($bulanIni, $tahun) * $this->nominal_spp;
    }
}
