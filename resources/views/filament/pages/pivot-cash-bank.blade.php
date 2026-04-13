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
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">Grand Total Cash</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($report['grand_total_cash'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">Grand Total Bank</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($report['grand_total_bank'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">Grand Total</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Rp {{ number_format($report['grand_total'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode Akun</th>
                        <th class="px-4 py-3 text-left">Nama Akun</th>
                        <th class="px-4 py-3 text-right">Cash</th>
                        <th class="px-4 py-3 text-right">Bank</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['kode'] }}</td>
                            <td class="px-4 py-3">{{ $row['nama'] }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($row['cash'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($row['bank'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada transaksi cash & bank pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
