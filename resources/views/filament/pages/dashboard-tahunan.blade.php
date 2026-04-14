<x-filament-panels::page>
    @php
        $report = $this->reportData;
        $rows = collect($report['rows']);
        $visibleRows = $this->showAllAccounts
            ? $rows
            : $rows->filter(fn (array $row): bool => (float) $row['akumulasi'] > 0 || (float) $row['anggaran'] > 0)->values();
        $hiddenRowsCount = max(0, $rows->count() - $visibleRows->count());
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];
        $quarterMap = [
            'Q1' => [1, 2, 3],
            'Q2' => [4, 5, 6],
            'Q3' => [7, 8, 9],
            'Q4' => [10, 11, 12],
        ];
        $quarterLabels = [
            'Q1' => 'Jan - Mar',
            'Q2' => 'Apr - Jun',
            'Q3' => 'Jul - Sep',
            'Q4' => 'Okt - Des',
        ];
        $quarterTotals = collect($quarterMap)->mapWithKeys(fn (array $months, string $quarter) => [
            $quarter => array_sum(array_map(fn (int $bulan) => $report['month_totals'][$bulan] ?? 0, $months)),
        ]);
        $quarterOpenings = collect($quarterMap)->mapWithKeys(fn (array $months, string $quarter) => [
            $quarter => array_sum(array_map(fn (int $bulan) => $report['opening_balances'][$bulan] ?? 0, $months)),
        ]);
        $focusedQuarter = $this->quarter !== 'ALL' ? $this->quarter : null;
        $focusedMonths = $focusedQuarter ? $quarterMap[$focusedQuarter] : [];
        $totalRealisasi = (float) $rows->sum('akumulasi');
        $totalAnggaran = (float) $rows->sum('anggaran');
        $coverage = $totalAnggaran > 0 ? round(($totalRealisasi / $totalAnggaran) * 100, 1) : null;
        $warningCount = $visibleRows->where('status', 'warning')->count();
        $dangerCount = $visibleRows->where('status', 'danger')->count();
        $highestQuarter = $quarterTotals->sortDesc()->keys()->first();
        $highestQuarterValue = $highestQuarter ? ($quarterTotals[$highestQuarter] ?? 0) : 0;
        $highestOpeningQuarter = $quarterOpenings->sortDesc()->keys()->first();
        $highestOpeningValue = $highestOpeningQuarter ? ($quarterOpenings[$highestOpeningQuarter] ?? 0) : 0;
    @endphp

    <div class="space-y-7">
        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-stone-100 via-white to-teal-50 text-slate-900 shadow-[0_24px_60px_-36px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 dark:text-white">
            <div class="grid gap-7 px-6 py-7 lg:grid-cols-[1.35fr_0.95fr] lg:px-8">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-teal-700 shadow-sm dark:border-white/15 dark:bg-white/10 dark:text-teal-200">
                        Monitoring Anggaran Tahunan
                    </div>

                    <div class="space-y-3">
                        <div class="text-2xl font-semibold tracking-tight lg:text-3xl">
                            Dashboard Tahunan Monitoring Anggaran
                        </div>
                        <div class="text-sm font-medium text-slate-600 dark:text-slate-300">
                            Tahun Buku {{ $this->tahun }}
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/8">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Total Realisasi</div>
                            <div class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Akumulasi seluruh akun dalam tahun berjalan</div>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/8">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Total Anggaran</div>
                            <div class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">Rp {{ number_format($totalAnggaran, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Target tahunan aktif dari modul anggaran</div>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/8">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Coverage</div>
                            <div class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">{{ $coverage !== null ? number_format($coverage, 1, ',', '.') . '%' : '-' }}</div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Realisasi dibandingkan target anggaran</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[24px] border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/8">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">Tahun Monitoring</label>
                        <select
                            wire:model.live="tahun"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 shadow-sm outline-none ring-0 transition focus:border-teal-400 dark:border-white/15 dark:bg-slate-900/70 dark:text-white dark:focus:border-cyan-300"
                        >
                            @foreach ($this->yearOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <div class="mt-4 rounded-2xl border border-teal-200 bg-teal-50 p-4 text-sm text-slate-700 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-50">
                            <div class="font-semibold text-slate-900 dark:text-white">Snapshot tahun {{ $this->tahun }}</div>
                            <div class="mt-1 text-slate-600 dark:text-cyan-100/80">
                                Quarter realisasi tertinggi:
                                <span class="font-semibold">{{ $highestQuarter ?? '-' }}</span>
                                (Rp {{ number_format($highestQuarterValue, 0, ',', '.') }})
                            </div>
                            <div class="mt-1 text-slate-600 dark:text-cyan-100/80">
                                Quarter saldo awal tertinggi:
                                <span class="font-semibold">{{ $highestOpeningQuarter ?? '-' }}</span>
                                (Rp {{ number_format($highestOpeningValue, 0, ',', '.') }})
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em]">Aman</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $visibleRows->where('status', 'success')->count() }}</div>
                            <div class="mt-1 text-xs text-emerald-700">Akun dalam batas yang sehat</div>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em]">Perlu Atensi</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $warningCount }}</div>
                            <div class="mt-1 text-xs text-amber-700">Mendekati ambang kontrol</div>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-sm">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em]">Kritis</div>
                            <div class="mt-2 text-2xl font-semibold">{{ $dangerCount }}</div>
                            <div class="mt-1 text-xs text-rose-700">Perlu tindakan cepat</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Quarter</div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Tabel utama disederhanakan per quarter agar lebih mudah dibaca.
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach (['ALL' => 'Semua', 'Q1' => 'Q1', 'Q2' => 'Q2', 'Q3' => 'Q3', 'Q4' => 'Q4'] as $value => $label)
                        <button
                            type="button"
                            wire:click="setQuarter('{{ $value }}')"
                            class="{{ $this->quarter === $value
                                ? 'bg-teal-700 text-white ring-teal-700 dark:bg-slate-100 dark:text-slate-900 dark:ring-slate-100'
                                : 'bg-stone-100 text-slate-600 ring-stone-200 hover:bg-stone-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700' }} rounded-full px-4 py-2 text-xs font-semibold transition ring-1"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 grid gap-4 {{ $focusedQuarter ? 'xl:grid-cols-4' : 'xl:grid-cols-4' }}">
                @if (! $focusedQuarter)
                    @foreach ($quarterMap as $quarter => $months)
                        <div class="rounded-[22px] border border-stone-200 bg-stone-50/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $quarter }}</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Rp {{ number_format($quarterTotals[$quarter], 0, ',', '.') }}</div>
                                </div>
                                <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-stone-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                                    {{ $quarterLabels[$quarter] }}
                                </div>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-200 dark:bg-slate-800">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-teal-500 via-cyan-500 to-sky-500"
                                    style="width: {{ $highestQuarterValue > 0 ? min(100, ($quarterTotals[$quarter] / $highestQuarterValue) * 100) : 0 }}%;"
                                ></div>
                            </div>
                            <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                                Saldo awal quarter: Rp {{ number_format($quarterOpenings[$quarter], 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach ($focusedMonths as $bulan)
                        <div class="rounded-[22px] border border-stone-200 bg-stone-50/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/60">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $monthNames[$bulan] }}</div>
                            <div class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">Rp {{ number_format($report['month_totals'][$bulan] ?? 0, 0, ',', '.') }}</div>
                            <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                                Saldo awal: Rp {{ number_format($report['opening_balances'][$bulan] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                    <div class="rounded-[22px] border border-teal-200 bg-teal-50 p-5 text-slate-800 shadow-sm dark:border-cyan-900 dark:bg-cyan-950/20 dark:text-cyan-50">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-700 dark:text-cyan-300">{{ $focusedQuarter }}</div>
                        <div class="mt-2 text-lg font-semibold">Rp {{ number_format($quarterTotals[$focusedQuarter], 0, ',', '.') }}</div>
                        <div class="mt-2 text-sm">{{ $quarterLabels[$focusedQuarter] }}</div>
                        <div class="mt-3 text-xs text-teal-700 dark:text-cyan-300">
                            Saldo awal quarter: Rp {{ number_format($quarterOpenings[$focusedQuarter], 0, ',', '.') }}
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_24px_60px_-38px_rgba(15,23,42,0.45)] ring-1 ring-black/5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">Matriks Monitoring Anggaran</div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Semua nilai dalam Rupiah. Tabel diringkas per quarter untuk mengurangi scroll horizontal.
                    </div>
                    @if ($hiddenRowsCount > 0 && ! $this->showAllAccounts)
                        <div class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ $hiddenRowsCount }} akun tanpa realisasi dan tanpa anggaran disembunyikan.
                        </div>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                    <button
                        type="button"
                        wire:click="setAccountVisibility(false)"
                        class="{{ ! $this->showAllAccounts
                            ? 'bg-teal-700 text-white ring-teal-700 dark:bg-slate-100 dark:text-slate-900 dark:ring-slate-100'
                            : 'bg-stone-100 text-slate-600 ring-stone-200 hover:bg-stone-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700' }} rounded-full px-3 py-1.5 transition ring-1"
                    >
                        Hanya akun aktif
                    </button>
                    <button
                        type="button"
                        wire:click="setAccountVisibility(true)"
                        class="{{ $this->showAllAccounts
                            ? 'bg-teal-700 text-white ring-teal-700 dark:bg-slate-100 dark:text-slate-900 dark:ring-slate-100'
                            : 'bg-stone-100 text-slate-600 ring-stone-200 hover:bg-stone-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700' }} rounded-full px-3 py-1.5 transition ring-1"
                    >
                        Tampilkan semua akun
                    </button>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 ring-1 ring-emerald-200">Aman</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 ring-1 ring-amber-200">Perlu atensi</span>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-rose-700 ring-1 ring-rose-200">Kritis</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1360px] border-separate border-spacing-0 text-[13px]">
                    <thead>
                        <tr class="bg-slate-800 text-white dark:bg-slate-950">
                            <th class="sticky left-0 z-30 min-w-[132px] border-b border-slate-700 bg-slate-800 px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800 dark:bg-slate-950">Kode</th>
                            <th class="sticky left-[132px] z-30 min-w-[280px] border-b border-slate-700 bg-slate-800 px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800 dark:bg-slate-950">Nama Akun</th>
                            @foreach (array_keys($quarterMap) as $quarter)
                                <th class="border-b border-slate-700 px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800">{{ $quarter }}</th>
                            @endforeach
                            <th class="border-b border-slate-700 px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800">Akumulasi</th>
                            <th class="border-b border-slate-700 px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800">Anggaran</th>
                            <th class="border-b border-slate-700 px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800">Progress</th>
                            <th class="border-b border-slate-700 px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.16em] dark:border-slate-800">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-cyan-50 dark:bg-cyan-950/20">
                            <td class="sticky left-0 z-20 min-w-[132px] border-b border-cyan-100 bg-cyan-50 px-3 py-2.5 font-mono text-xs font-semibold text-cyan-900 dark:border-cyan-900 dark:bg-cyan-950/20 dark:text-cyan-100">
                                OPEN
                            </td>
                            <td class="sticky left-[132px] z-20 min-w-[280px] border-b border-cyan-100 bg-cyan-50 px-3 py-2.5 font-semibold text-cyan-950 dark:border-cyan-900 dark:bg-cyan-950/20 dark:text-cyan-50">
                                Saldo Awal Operasional
                            </td>
                            @foreach (array_keys($quarterMap) as $quarter)
                                <td class="border-b border-cyan-100 px-3 py-2.5 text-right font-semibold text-cyan-900 dark:border-cyan-900 dark:text-cyan-100">
                                    {{ number_format($quarterOpenings[$quarter], 0, ',', '.') }}
                                </td>
                            @endforeach
                            <td class="border-b border-cyan-100 px-3 py-2.5 text-right font-bold text-cyan-900 dark:border-cyan-900 dark:text-cyan-100">
                                {{ number_format(array_sum($report['opening_balances']), 0, ',', '.') }}
                            </td>
                            <td class="border-b border-cyan-100 px-3 py-2.5 text-right text-cyan-700 dark:border-cyan-900 dark:text-cyan-300">-</td>
                            <td class="border-b border-cyan-100 px-3 py-2.5 text-cyan-700 dark:border-cyan-900 dark:text-cyan-300">Saldo awal per quarter</td>
                            <td class="border-b border-cyan-100 px-3 py-2.5 text-right text-cyan-700 dark:border-cyan-900 dark:text-cyan-300">-</td>
                        </tr>

                        @foreach ($visibleRows as $row)
                            @php
                                $badgeClasses = match ($row['status']) {
                                    'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
                                    'warning' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
                                    'danger' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
                                    default => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                                };
                                $progressBarClasses = match ($row['status']) {
                                    'success' => 'from-emerald-400 to-emerald-600',
                                    'warning' => 'from-amber-400 to-orange-500',
                                    'danger' => 'from-rose-400 to-rose-600',
                                    default => 'from-slate-400 to-slate-500',
                                };
                                $progressValue = $row['persen'] !== null ? min(100, $row['persen']) : 0;
                                $rowSurface = $loop->odd ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/60 dark:bg-slate-800/70';
                            @endphp
                            <tr class="{{ $rowSurface }} hover:bg-sky-50/60 dark:hover:bg-slate-800">
                                <td class="sticky left-0 z-10 min-w-[132px] border-b border-slate-200 px-3 py-2.5 font-mono text-xs font-semibold text-slate-700 dark:border-slate-800 dark:text-slate-300 {{ $rowSurface }}">
                                    {{ $row['kode'] }}
                                </td>
                                <td class="sticky left-[132px] z-10 min-w-[280px] border-b border-slate-200 px-3 py-2.5 dark:border-slate-800 {{ $rowSurface }}">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ $row['nama'] }}</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                                        {{ $row['kategori'] }} - {{ $row['tipe'] }}
                                    </div>
                                </td>
                                @foreach ($quarterMap as $months)
                                    <td class="border-b border-slate-200 px-3 py-2.5 text-right text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        {{ number_format(array_sum(array_map(fn (int $bulan) => $row['months'][$bulan] ?? 0, $months)), 0, ',', '.') }}
                                    </td>
                                @endforeach
                                <td class="border-b border-slate-200 px-3 py-2.5 text-right font-semibold text-slate-900 dark:border-slate-800 dark:text-white">
                                    {{ number_format($row['akumulasi'], 0, ',', '.') }}
                                </td>
                                <td class="border-b border-slate-200 px-3 py-2.5 text-right text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                    {{ number_format($row['anggaran'], 0, ',', '.') }}
                                </td>
                                <td class="border-b border-slate-200 px-3 py-2.5 dark:border-slate-800">
                                    <div class="min-w-[160px]">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $badgeClasses }}">
                                            {{ $row['persen'] !== null ? number_format($row['persen'], 1, ',', '.') . '%' : 'Tanpa anggaran' }}
                                        </span>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                            <div
                                                class="h-full rounded-full bg-gradient-to-r {{ $progressBarClasses }}"
                                                style="width: {{ $progressValue }}%;"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="border-b border-slate-200 px-3 py-2.5 text-right font-medium {{ $row['selisih'] >= 0 ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }} dark:border-slate-800">
                                    {{ number_format($row['selisih'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
