@if ($status)
    @php
        $toneClasses = [
            'amber' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-100',
            'slate' => 'border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
        ][$status['tone'] ?? 'slate'];
    @endphp

    <div class="rounded-xl border px-4 py-3 text-sm {{ $toneClasses }}">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="inline-flex rounded-full border border-current/20 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                SmartSIS Sync {{ $status['status_label'] }}
            </span>
            <span>Tahun {{ $status['tahun'] }}</span>
            @if ($status['requested_at'])
                <span>Dibuat {{ $status['requested_at'] }}</span>
            @endif
            @if ($status['requested_by'])
                <span>Oleh {{ $status['requested_by'] }}</span>
            @endif
        </div>

        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
            @if ($status['started_at'])
                <span>Mulai: {{ $status['started_at'] }}</span>
            @endif
            @if ($status['finished_at'])
                <span>Selesai: {{ $status['finished_at'] }}</span>
            @endif
            @if ($status['months_count'] > 0)
                <span>Bulan tersync: {{ $status['months_count'] }}</span>
            @endif
            @if ($status['payments_fetched'] > 0)
                <span>Pembayaran: {{ number_format($status['payments_fetched'], 0, ',', '.') }}</span>
            @endif
            @if ($status['students_fetched'] > 0)
                <span>Siswa aktif: {{ number_format($status['students_fetched'], 0, ',', '.') }}</span>
            @endif
            @if ($status['arrears_synced_months'] > 0)
                <span>Tunggakan tersync: {{ $status['arrears_synced_months'] }} bulan</span>
            @endif
            @if ($status['warning_count'] > 0)
                <span>Warning: {{ $status['warning_count'] }}</span>
            @endif
        </div>

        @if ($status['error_message'])
            <div class="mt-2 text-xs font-medium">
                {{ $status['error_message'] }}
            </div>
        @endif
    </div>
@endif
