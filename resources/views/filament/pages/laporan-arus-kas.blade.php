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

            @if (auth()->user()?->isAdmin())
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-filament::button wire:click="simpanSaldoAwal" color="gray" :disabled="$report['is_locked']">
                        Simpan Saldo Awal
                    </x-filament::button>

                    <x-filament::button wire:click="kunciBulan" color="success" :disabled="$report['is_locked']">
                        Kunci Bulan Ini
                    </x-filament::button>
                </div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">Saldo Awal</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                    Rp {{ number_format($report['saldo_awal_total'], 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    Cash Rp {{ number_format($report['saldo_awal_cash'], 0, ',', '.') }} | Bank Rp {{ number_format($report['saldo_awal_bank'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="text-sm text-emerald-700 dark:text-emerald-300">Total Penerimaan</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">
                    Rp {{ number_format($report['total_masuk'], 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-emerald-700/80 dark:text-emerald-300/80">
                    Cash Rp {{ number_format($report['total_masuk_cash'], 0, ',', '.') }} | Bank Rp {{ number_format($report['total_masuk_bank'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-800 dark:bg-rose-900/20">
                <div class="text-sm text-rose-700 dark:text-rose-300">Pengeluaran Besar</div>
                <div class="mt-2 text-2xl font-semibold text-rose-800 dark:text-rose-200">
                    Rp {{ number_format($report['total_keluar_besar'], 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-rose-700/80 dark:text-rose-300/80">
                    Cash Rp {{ number_format($report['total_keluar_besar_cash'], 0, ',', '.') }} | Bank Rp {{ number_format($report['total_keluar_besar_bank'], 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-900/20">
                <div class="text-sm text-amber-700 dark:text-amber-300">Kas Kecil</div>
                <div class="mt-2 text-2xl font-semibold text-amber-800 dark:text-amber-200">
                    Rp {{ number_format($report['total_kas_kecil'], 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-amber-700/80 dark:text-amber-300/80">
                    Pengeluaran petty cash bulan ini
                </div>
            </div>

            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-800 dark:bg-sky-900/20">
                <div class="text-sm text-sky-700 dark:text-sky-300">Saldo Akhir</div>
                <div class="mt-2 text-2xl font-semibold text-sky-800 dark:text-sky-200">
                    Rp {{ number_format($report['saldo_akhir_total'], 0, ',', '.') }}
                </div>
                <div class="mt-2 text-xs text-sky-700/80 dark:text-sky-300/80">
                    Cash Rp {{ number_format($report['saldo_akhir_cash'], 0, ',', '.') }} | Bank Rp {{ number_format($report['saldo_akhir_bank'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                    Penerimaan
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-left">Akun</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($report['penerimaan'] as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                                <td class="px-4 py-2">{{ $row['nama'] }}</td>
                                <td class="px-4 py-2 text-right font-medium text-emerald-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400">Belum ada penerimaan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                    Pengeluaran Besar
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-left">Akun</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($report['pengeluaran_besar'] as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                                <td class="px-4 py-2">{{ $row['nama'] }}</td>
                                <td class="px-4 py-2 text-right font-medium text-rose-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400">Belum ada pengeluaran besar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                    Pengeluaran Kas Kecil
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-left">Akun</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($report['pengeluaran_kas_kecil'] as $row)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                                <td class="px-4 py-2">{{ $row['nama'] }}</td>
                                <td class="px-4 py-2 text-right font-medium text-amber-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400">Belum ada pengeluaran kas kecil.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
