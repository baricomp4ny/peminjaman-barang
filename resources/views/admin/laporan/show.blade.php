<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Laporan</span></x-slot>

    <x-admin.judul :judul="$laporan['judul']">
        @php $q = request()->only(['dari', 'sampai']); @endphp
        <x-link-button variant="secondary" :href="route('admin.laporan.show', array_merge(['jenis' => $laporan['jenis'], 'format' => 'pdf'], $q))">Unduh PDF</x-link-button>
        <x-link-button :href="route('admin.laporan.show', array_merge(['jenis' => $laporan['jenis'], 'format' => 'excel'], $q))">Unduh Excel</x-link-button>
    </x-admin.judul>

    <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        @if ($laporan['jenis'] !== 'inventaris')
            <form method="GET" class="flex flex-wrap items-end gap-2 border-b p-4">
                <div>
                    <x-input-label for="dari" value="Dari" />
                    <x-text-input id="dari" type="date" name="dari" :value="$dari" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="sampai" value="Sampai" />
                    <x-text-input id="sampai" type="date" name="sampai" :value="$sampai" class="mt-1" />
                </div>
                <x-secondary-button type="submit">Tampilkan</x-secondary-button>
                @if ($errors->any())
                    <div class="w-full"><x-input-error :messages="$errors->all()" /></div>
                @endif
            </form>
        @endif

        <div class="flex flex-wrap gap-x-6 gap-y-1 border-b px-4 py-3 text-sm">
            <span class="text-marian-500">Periode: <strong class="text-marian-900">{{ $laporan['periode'] }}</strong></span>
            @foreach ($laporan['ringkasan'] as $label => $nilai)
                <span class="text-marian-500">{{ $label }}: <strong class="text-marian-900">{{ $nilai }}</strong></span>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-marian-100 text-sm">
                <thead class="bg-marian-50 text-left text-xs font-semibold uppercase text-marian-500">
                    <tr>
                        @foreach ($laporan['kolom'] as $k)
                            <th class="whitespace-nowrap px-3 py-2">{{ $k }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-marian-50">
                    @forelse ($laporan['baris'] as $baris)
                        <tr>
                            @foreach ($baris as $sel)
                                <td class="whitespace-nowrap px-3 py-2">{{ $sel }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($laporan['kolom']) }}" class="px-4 py-8 text-center text-marian-500">Tidak ada data pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
