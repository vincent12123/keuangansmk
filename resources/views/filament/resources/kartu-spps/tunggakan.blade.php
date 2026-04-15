<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                <select wire:model.live="bulan" class="rounded-lg border-gray-300 text-sm">
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $b => $label)
                        <option value="{{ $b }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                <select wire:model.live="tahun" class="rounded-lg border-gray-300 text-sm">
                    @foreach ([date('Y'), date('Y') - 1] as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
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

            @if (config('spp_integration.enabled'))
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                    <select wire:model.live="filterKelas" class="rounded-lg border-gray-300 text-sm">
                        <option value="">Semua Kelas</option>
                        @foreach ($this->kelasOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="ml-auto flex items-center gap-3">
                <span class="text-sm text-gray-500">
                    {{ count($this->tunggakanData) }} siswa belum bayar SPP {{ $this->namaBulan }} {{ $this->tahun }}
                </span>
            </div>
        </div>

@if (config('spp_integration.enabled'))
    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-200">
        Data tunggakan menggunakan hasil sinkronisasi SmartSIS di database lokal.
    </div>
@endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">No</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">NIS</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Nama Siswa</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Kelas</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Jurusan</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Nominal SPP</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">No HP Wali</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($this->tunggakanData as $i => $siswa)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $siswa['nis'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $siswa['nama'] }}</td>
                            <td class="px-4 py-3">{{ $siswa['kelas'] }}</td>
                            <td class="px-4 py-3">{{ $siswa['jurusan'] }}</td>
                            <td class="px-4 py-3 text-right font-medium text-red-600">
                                Rp {{ number_format($siswa['nominal_spp'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                {{ $siswa['no_hp_wali'] ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                Semua siswa sudah membayar SPP {{ $this->namaBulan }} {{ $this->tahun }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if (count($this->tunggakanData) > 0)
                    <tfoot class="bg-red-50 dark:bg-red-900/20">
                        <tr>
                            <td colspan="5" class="px-4 py-3 font-medium text-red-700 dark:text-red-300">
                                Total Tunggakan ({{ count($this->tunggakanData) }} siswa)
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-300">
                                Rp {{ number_format(collect($this->tunggakanData)->sum('nominal_spp'), 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
