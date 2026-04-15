<x-filament-panels::page>
    @php($report = $this->reportData)
    @php($sourceLabel = [
        'stored' => 'Saldo awal tersimpan untuk bulan ini',
        'previous_locked' => 'Otomatis dibawa dari saldo akhir bulan terkunci sebelumnya',
        'default_zero' => 'Belum ada saldo awal tersimpan, memakai nilai 0',
    ][$report['opening_source']] ?? 'Saldo awal')

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid gap-4 lg:grid-cols-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                    <select wire:model.live="bulan" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($this->monthOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                    <select wire:model.live="tahun" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach ($this->yearOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Saldo Awal Cash</label>
                    <input type="number" step="0.01" wire:model.defer="saldoAwalCash" class="w-full rounded-lg border-gray-300 text-sm" @disabled($report['is_locked']) />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Saldo Awal Bank</label>
                    <input type="number" step="0.01" wire:model.defer="saldoAwalBank" class="w-full rounded-lg border-gray-300 text-sm" @disabled($report['is_locked']) />
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Bulan</label>
                    <div class="flex h-10 items-center rounded-lg border border-dashed border-gray-300 px-3 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                        {{ $report['is_locked'] ? 'Sudah dikunci' : 'Masih terbuka' }} | {{ $sourceLabel }}
                    </div>
                </div>
            </div>

            @include('filament.partials.smartsis-sync-status', ['status' => $this->latestSmartsisSyncStatus])

            @if ($report['external_spp']['enabled'] ?? false)
                <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-200">
                    SPP bulan ini memakai integrasi SmartSIS.
                    Sumber:
                    {{ $report['external_spp']['source'] === 'remote' ? 'API langsung' : ($report['external_spp']['source'] === 'cache' ? 'cache lokal terakhir' : ($report['external_spp']['source'] === 'database_sync' ? 'jurnal hasil sync database' : 'tidak tersedia')) }}.
                </div>
            @endif

            @if (auth()->user()?->isAdmin())
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-filament::button wire:click="simpanSaldoAwal" color="gray" :disabled="$report['is_locked']">
                        Simpan Saldo Awal
                    </x-filament::button>

                    <x-filament::button wire:click="kunciBulan" color="success" :disabled="$report['is_locked']">
                        Kunci Bulan
                    </x-filament::button>

                    <x-filament::button wire:click="bukaKunciBulan" color="warning" :disabled="! $report['is_locked']">
                        Buka Kunci
                    </x-filament::button>
                </div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">A. Saldo Awal</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                    Rp {{ number_format($report['saldo_awal_total'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="text-sm text-emerald-700 dark:text-emerald-300">B. Total Penerimaan</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">
                    Rp {{ number_format($report['total_masuk'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-800 dark:bg-rose-900/20">
                <div class="text-sm text-rose-700 dark:text-rose-300">C. Total Pengeluaran</div>
                <div class="mt-2 text-2xl font-semibold text-rose-800 dark:text-rose-200">
                    Rp {{ number_format($report['total_pengeluaran'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-900/20">
                <div class="text-sm text-amber-700 dark:text-amber-300">Selisih</div>
                <div class="mt-2 text-2xl font-semibold text-amber-800 dark:text-amber-200">
                    Rp {{ number_format($report['selisih'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-800 dark:bg-sky-900/20">
                <div class="text-sm text-sky-700 dark:text-sky-300">D. Saldo Akhir</div>
                <div class="mt-2 text-2xl font-semibold text-sky-800 dark:text-sky-200">
                    Rp {{ number_format($report['saldo_akhir_total'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 shadow-sm dark:border-violet-800 dark:bg-violet-900/20">
                <div class="text-sm text-violet-700 dark:text-violet-300">Kas Besar / Kecil</div>
                <div class="mt-2 text-sm font-semibold text-violet-800 dark:text-violet-200">
                    D1 Rp {{ number_format($report['saldo_kas_kecil'], 0, ',', '.') }}<br>
                    D2 Rp {{ number_format($report['saldo_kas_besar'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                B. Penerimaan
            </div>
            <div class="space-y-4 p-4">
                @foreach ($report['penerimaan_sections'] as $sectionKey => $section)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-700">
                            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $sectionKey }}. {{ $section['title'] }}</div>
                            <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($section['total'], 0, ',', '.') }}</div>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-white dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Kode</th>
                                    <th class="px-4 py-2 text-left">Nama Akun</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($section['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                                        <td class="px-4 py-2 text-right font-medium text-emerald-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-gray-400">Tidak ada transaksi pada kelompok ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                C. Pengeluaran
            </div>
            <div class="space-y-4 p-4">
                @foreach ($report['pengeluaran_sections'] as $sectionKey => $section)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-700">
                            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $sectionKey }}. {{ $section['title'] }}</div>
                            <div class="text-sm font-semibold text-rose-700 dark:text-rose-300">Rp {{ number_format($section['total'], 0, ',', '.') }}</div>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-white dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Kode</th>
                                    <th class="px-4 py-2 text-left">Nama Akun</th>
                                    <th class="px-4 py-2 text-right">Total Gabungan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($section['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                                        <td class="px-4 py-2 text-right font-medium text-rose-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-gray-400">Tidak ada transaksi pada kelompok ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">D1. Saldo Kas Kecil</div>
                <div class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <div>Pengisian Kas Kecil: <span class="font-semibold">Rp {{ number_format($report['total_pengisian_kas_kecil'], 0, ',', '.') }}</span></div>
                    <div>Pengeluaran Kas Kecil: <span class="font-semibold">Rp {{ number_format($report['total_kas_kecil'], 0, ',', '.') }}</span></div>
                    <div class="border-t border-gray-200 pt-2 font-semibold dark:border-gray-700">
                        Saldo Kas Kecil: Rp {{ number_format($report['saldo_kas_kecil'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">D2. Saldo Kas Besar</div>
                <div class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <div>Saldo Akhir Operasional (D): <span class="font-semibold">Rp {{ number_format($report['saldo_akhir_total'], 0, ',', '.') }}</span></div>
                    <div>Saldo Kas Kecil (D1): <span class="font-semibold">Rp {{ number_format($report['saldo_kas_kecil'], 0, ',', '.') }}</span></div>
                    <div class="border-t border-gray-200 pt-2 font-semibold dark:border-gray-700">
                        Saldo Kas Besar: Rp {{ number_format($report['saldo_kas_besar'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
