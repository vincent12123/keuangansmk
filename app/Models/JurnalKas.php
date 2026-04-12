<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalKas extends Model
{
    use SoftDeletes;

    public const SPP_ACCOUNT_CODES = [
        '4.01.01.00',
        '4.01.02.00',
        '4.01.03.00',
    ];

    protected $table = 'jurnal_kas';

    protected $fillable = [
        'no_kwitansi', 'tanggal', 'nis', 'nama_penyetor',
        'kelas_id', 'kode_akun_id', 'uraian',
        'cash', 'bank', 'jenis',
        'bulan', 'tahun',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'cash'    => 'decimal:2',
        'bank'    => 'decimal:2',
        'bulan'   => 'integer',
        'tahun'   => 'integer',
    ];

    // ─── Boot ────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            static::syncDerivedAttributes($model);

            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (self $model) {
            static::syncDerivedAttributes($model);

            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────
    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kartuSpp(): HasMany
    {
        return $this->hasMany(KartuSpp::class, 'jurnal_kas_id');
    }

    // ─── Scopes ──────────────────────────────────────────────
    public function scopeBulanIni($query)
    {
        return $query->where('bulan', now()->month)->where('tahun', now()->year);
    }

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function scopeMasuk($query)
    {
        return $query->where('jenis', 'masuk');
    }

    public function scopeKeluar($query)
    {
        return $query->where('jenis', 'keluar');
    }

    // ─── Helpers ─────────────────────────────────────────────
    public function getTotalAttribute(): float
    {
        return (float) $this->cash + (float) $this->bank;
    }

    // Generate nomor kwitansi otomatis
    public static function generateNoKwitansi(): string
    {
        $last = static::whereYear('created_at', now()->year)
            ->whereNotNull('no_kwitansi')
            ->orderByDesc('no_kwitansi')
            ->value('no_kwitansi');

        $lastNum = $last ? (int) ltrim($last, '0') : 0;
        return str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
    }

    protected static function syncDerivedAttributes(self $model): void
    {
        if ($model->tanggal) {
            $model->bulan = $model->tanggal->month;
            $model->tahun = $model->tanggal->year;
        }

        $kodeAkun = $model->relationLoaded('kodeAkun')
            ? $model->kodeAkun
            : KodeAkun::find($model->kode_akun_id);

        if (! $kodeAkun) {
            return;
        }

        $model->jenis = $kodeAkun->tipe === 'pendapatan'
            ? 'masuk'
            : 'keluar';

        if ($kodeAkun->tipe === 'pendapatan' && blank($model->no_kwitansi)) {
            $model->no_kwitansi = static::generateNoKwitansi();
        }
    }
}
