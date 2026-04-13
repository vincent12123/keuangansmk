<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasKecil extends Model
{
    use SoftDeletes;

    protected $table = 'kas_kecil';

    protected $fillable = [
        'no_ref', 'tanggal', 'kode_akun_id',
        'uraian', 'nominal', 'bulan', 'tahun',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'bulan'   => 'integer',
        'tahun'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if ($model->tanggal) {
                $model->bulan = $model->tanggal->month;
                $model->tahun = $model->tanggal->year;
            }

            // Auto-generate no_ref: K25-0001
            if (! $model->no_ref) {
                $tahun2digit = $model->tanggal?->format('y') ?? now()->format('y');
                $last = static::where('no_ref', 'like', "K{$tahun2digit}-%")
                    ->orderByDesc('id')
                    ->value('no_ref');
                $lastNum = $last ? (int) substr($last, 4) : 0;
                $model->no_ref = "K{$tahun2digit}-" . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    // Total pengeluaran kas kecil bulan tertentu per kode akun
    public static function totalPerKode(int $bulan, int $tahun): array
    {
        return static::bulanTahun($bulan, $tahun)
            ->with('kodeAkun')
            ->selectRaw('kode_akun_id, SUM(nominal) as total')
            ->groupBy('kode_akun_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->kode_akun_id => $row->total])
            ->toArray();
    }
}
