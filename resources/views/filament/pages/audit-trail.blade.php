<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis Log</label>
                    <select wire:model.live="logName" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach ($this->logNameOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Event</label>
                    <select wire:model.live="event" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach ($this->eventOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">User</label>
                    <select wire:model.live="causerId" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach ($this->causerOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Per Halaman</label>
                    <select wire:model.live="perPage" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari</label>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Cari deskripsi, subject, jenis log, atau properti..."
                        class="w-full rounded-lg border-gray-300 text-sm"
                    />
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <x-filament::button color="gray" wire:click="resetFilters">
                    Reset Filter
                </x-filament::button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-100">
                Aktivitas Tercatat
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">Log</th>
                            <th class="px-4 py-3 text-left">Deskripsi</th>
                            <th class="px-4 py-3 text-left">Subject</th>
                            <th class="px-4 py-3 text-left">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($this->activities as $activity)
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ $activity->created_at?->format('d/m/Y H:i:s') }}</div>
                                    <div class="text-xs text-gray-500">{{ $activity->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    {{ $this->causerLabel($activity) }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="inline-flex rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-700">
                                        {{ $activity->log_name ?? '-' }}
                                    </div>
                                    @if ($activity->event)
                                        <div class="mt-2 text-xs text-gray-500">{{ $activity->event }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $activity->description }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    {{ $this->subjectLabel($activity) }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <details class="rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-900/30">
                                        <summary class="cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-200">Lihat properti</summary>
                                        <pre class="mt-2 overflow-x-auto whitespace-pre-wrap text-[11px] text-gray-600 dark:text-gray-300">{{ $this->propertiesJson($activity) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                    Belum ada aktivitas yang tercatat untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $this->activities->links() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
