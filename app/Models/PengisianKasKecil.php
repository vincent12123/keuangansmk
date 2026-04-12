<?php
// ─── PengisianKasKecil.php ───────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengisianKasKecil extends Model
{
    protected $table = 'pengisian_kas_kecil';

    protected $fillable = [
        'tanggal', 'nominal', 'keterangan',
        'bulan', 'tahun', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }
}
