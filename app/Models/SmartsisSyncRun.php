<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartsisSyncRun extends Model
{
    protected $fillable = [
        'sync_type',
        'tahun',
        'status',
        'requested_by',
        'started_at',
        'finished_at',
        'months_synced',
        'result_summary',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'months_synced' => 'array',
            'result_summary' => 'array',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function scopeLatestForYear($query, int $tahun)
    {
        return $query
            ->where('sync_type', 'year_to_date')
            ->where('tahun', $tahun)
            ->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['queued', 'running']);
    }
}
