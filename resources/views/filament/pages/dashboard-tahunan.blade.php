<x-filament-panels::page>
    @php($report = $this->reportData)

    <div class="space-y-6">
        <div class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                <select wire:model.live="tahun" class="rounded-lg border-gray-300 text-sm">
                    @foreach ($this->yearOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ml-auto text-sm text-gray-500">
                Monitoring realisasi vs anggaran {{ $this->tahun }}
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="min-w-[1600px] text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Nama Akun</th>
                        @foreach (range(1, 12) as $bulan)
                            <th class="px-4 py-3 text-right">{{ $bulan }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Akumulasi</th>
                        <th class="px-4 py-3 text-right">Anggaran</th>
                        <th class="px-4 py-3 text-right">%</th>
                        <th class="px-4 py-3 text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr class="bg-sky-50 dark:bg-sky-900/20">
                        <td class="px-4 py-3 font-semibold" colspan="2">Saldo Awal Operasional</td>
                        @foreach (range(1, 12) as $bulan)
                            <td class="px-4 py-3 text-right font-medium text-sky-800 dark:text-sky-200">
                                Rp {{ number_format($report['opening_balances'][$bulan] ?? 0, 0, ',', '.') }}
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right font-bold text-sky-800 dark:text-sky-200">Rp {{ number_format(array_sum($report['opening_balances']), 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">-</td>
                        <td class="px-4 py-3 text-right">-</td>
                        <td class="px-4 py-3 text-right">-</td>
                    </tr>

                    @foreach ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['kode'] }}</td>
                            <td class="px-4 py-3">{{ $row['nama'] }}</td>
                            @foreach (range(1, 12) as $bulan)
                                <td class="px-4 py-3 text-right">Rp {{ number_format($row['months'][$bulan], 0, ',', '.') }}</td>
                            @endforeach
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($row['akumulasi'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($row['anggaran'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <span @class([
                                    'font-semibold',
                                    'text-emerald-700' => $row['status'] === 'success',
                                    'text-amber-700' => $row['status'] === 'warning',
                                    'text-rose-700' => $row['status'] === 'danger',
                                    'text-gray-400' => $row['status'] === 'gray',
                                ])>
                                    {{ $row['persen'] !== null ? number_format($row['persen'], 2, ',', '.') . '%' : '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($row['selisih'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
