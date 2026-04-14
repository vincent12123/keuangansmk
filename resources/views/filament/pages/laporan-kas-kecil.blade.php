<x-filament-panels::page>
    @php($report = $this->reportData)

    <div class="space-y-6">
        <div class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                <select wire:model.live="bulan" class="rounded-lg border-gray-300 text-sm">
                    @foreach ($this->monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                <select wire:model.live="tahun" class="rounded-lg border-gray-300 text-sm">
                    @foreach ($this->yearOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="ml-auto text-sm text-gray-500">
                Pivot kas kecil untuk {{ $this->monthOptions[$this->bulan] ?? $this->bulan }} {{ $this->tahun }}
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="text-sm text-emerald-700 dark:text-emerald-300">Total Pengisian</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">Rp {{ number_format($report['total_pengisian'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-800 dark:bg-rose-900/20">
                <div class="text-sm text-rose-700 dark:text-rose-300">Grand Total Pivot</div>
                <div class="mt-2 text-2xl font-semibold text-rose-800 dark:text-rose-200">Rp {{ number_format($report['pivot']->sum('total'), 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 shadow-sm dark:border-sky-800 dark:bg-sky-900/20">
                <div class="text-sm text-sky-700 dark:text-sky-300">Saldo Kas Kecil</div>
                <div class="mt-2 text-2xl font-semibold text-sky-800 dark:text-sky-200">Rp {{ number_format($report['saldo'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border {{ $report['validation_diff'] > 0 ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-4 shadow-sm">
                <div class="text-sm {{ $report['validation_diff'] > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-gray-300' }}">Validasi ke Arus Kas</div>
                <div class="mt-2 text-2xl font-semibold {{ $report['validation_diff'] > 0 ? 'text-amber-800 dark:text-amber-200' : 'text-gray-900 dark:text-white' }}">Rp {{ number_format($report['validation_diff'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">Pivot Rekap Kas Kecil</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Nama Akun</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($report['pivot'] as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs">{{ $row['kode'] }}</td>
                            <td class="px-4 py-2">{{ $row['nama'] }}</td>
                            <td class="px-4 py-2 text-right font-medium text-rose-700">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400">Belum ada data pivot kas kecil.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">Detail Transaksi Kas Kecil</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Ref</th>
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Uraian</th>
                        <th class="px-4 py-2 text-right">Nominal</th>
                        <th class="px-4 py-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($report['transactions'] as $row)
                        <tr>
                            <td class="px-4 py-2">{{ $row['tanggal']?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $row['no_ref'] }}</td>
                            <td class="px-4 py-2">
                                <div class="font-mono text-xs">{{ $row['kode'] }}</div>
                                <div class="text-gray-500">{{ $row['nama'] }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $row['uraian'] }}</td>
                            <td class="px-4 py-2 text-right font-medium text-rose-700">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-medium text-sky-700">Rp {{ number_format($row['saldo'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada transaksi kas kecil.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
