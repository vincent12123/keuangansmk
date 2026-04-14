<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KodeAkun extends Model
{
    protected $table = 'kode_akun';

    protected $fillable = [
        'kode', 'nama', 'tipe', 'kategori',
        'sub_kategori', 'aktif', 'kas_kecil',
    ];

    protected $casts = [
        'aktif'     => 'boolean',
        'kas_kecil' => 'boolean',
    ];

    // ─── Scopes ──────────────────────────────────────────────
    public function scopePendapatan($query)
    {
        return $query->where('tipe', 'pendapatan');
    }

    public function scopePengeluaran($query)
    {
        return $query->where('tipe', 'pengeluaran');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeUntukKasKecil($query)
    {
        return $query->where('kas_kecil', true)->where('aktif', true);
    }

    // Kode yang berisi sub-akun (tidak dipakai langsung di transaksi)
    public function scopeTransaksional($query)
    {
        return $query
            ->where('aktif', true)
            ->whereNotNull('sub_kategori')
            ->whereNotExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('kode_akun as child_accounts')
                    ->whereColumn('child_accounts.kode', '!=', 'kode_akun.kode')
                    ->whereRaw("child_accounts.kode LIKE CONCAT(LEFT(kode_akun.kode, LENGTH(kode_akun.kode) - 2), '__')");
            });
    }

    // ─── Relations ───────────────────────────────────────────
    public function jurnalKas(): HasMany
    {
        return $this->hasMany(JurnalKas::class);
    }

    public function kasKecil(): HasMany
    {
        return $this->hasMany(KasKecil::class);
    }

    public function anggaran(): HasMany
    {
        return $this->hasMany(Anggaran::class);
    }

    // ─── Helpers ─────────────────────────────────────────────
    public function getLabelAttribute(): string
    {
        return "[{$this->kode}] {$this->nama}";
    }

    public function isHeader(): bool
    {
        return blank($this->sub_kategori);
    }
}
