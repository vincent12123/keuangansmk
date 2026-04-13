<?php
// ─── PengisianKasKecil.php ───────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PengisianKasKecil extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'pengisian_kas_kecil';

    protected $fillable = [
        'tanggal', 'nominal', 'keterangan',
        'bulan', 'tahun', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->tanggal) {
                $tanggal = $model->tanggal instanceof Carbon
                    ? $model->tanggal
                    : Carbon::parse($model->tanggal);

                $model->bulan = $tanggal->month;
                $model->tahun = $tanggal->year;
            }

            if (auth()->check() && blank($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function (self $model): void {
            if ($model->tanggal) {
                $tanggal = $model->tanggal instanceof Carbon
                    ? $model->tanggal
                    : Carbon::parse($model->tanggal);

                $model->bulan = $tanggal->month;
                $model->tahun = $tanggal->year;
            }

            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeBulanTahun($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('pengisian_kas_kecil')
            ->logOnly([
                'tanggal',
                'nominal',
                'keterangan',
                'bulan',
                'tahun',
                'created_by',
                'updated_by',
                'deleted_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'create_pengisian_kas_kecil',
                'updated' => 'update_pengisian_kas_kecil',
                'deleted' => 'delete_pengisian_kas_kecil',
                default => $eventName,
            });
    }
}
