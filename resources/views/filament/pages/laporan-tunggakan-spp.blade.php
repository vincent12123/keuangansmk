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

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jurusan</label>
                <select wire:model.live="filterJurusan" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Semua Jurusan</option>
                    @foreach ($this->jurusanOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select wire:model.live="filterKelas" class="rounded-lg border-gray-300 text-sm">
                    <option value="">Semua Kelas</option>
                    @foreach ($this->kelasOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="text-sm text-gray-500">Siswa Aktif</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $report['total_siswa_aktif'] }}</div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/20">
                <div class="text-sm text-emerald-700 dark:text-emerald-300">Sudah Bayar</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-800 dark:text-emerald-200">{{ $report['total_sudah_bayar'] }}</div>
                <div class="mt-2 text-xs text-emerald-700/80 dark:text-emerald-300/80">{{ number_format($report['persen_sudah_bayar'], 2, ',', '.') }}%</div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-800 dark:bg-rose-900/20">
                <div class="text-sm text-rose-700 dark:text-rose-300">Belum Bayar</div>
                <div class="mt-2 text-2xl font-semibold text-rose-800 dark:text-rose-200">{{ $report['total_belum_bayar'] }}</div>
                <div class="mt-2 text-xs text-rose-700/80 dark:text-rose-300/80">{{ number_format($report['persen_belum_bayar'], 2, ',', '.') }}%</div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-900/20">
                <div class="text-sm text-amber-700 dark:text-amber-300">Total Nominal Tunggakan</div>
                <div class="mt-2 text-2xl font-semibold text-amber-800 dark:text-amber-200">
                    Rp {{ number_format($report['total_nominal_tunggakan'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">No</th>
                        <th class="px-4 py-3 text-left">NIS</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left">Jurusan</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-left">HP Wali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($report['rows'] as $index => $row)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['nis'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['nama'] }}</td>
                            <td class="px-4 py-3">{{ $row['kelas'] }}</td>
                            <td class="px-4 py-3">{{ $row['jurusan'] }}</td>
                            <td class="px-4 py-3 text-right font-medium text-rose-700">Rp {{ number_format($row['nominal_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['no_hp_wali'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                Tidak ada tunggakan pada filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
