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

        @php
            // Kelompokkan ringkasan per awalan label ("Kondisi …", "Status …", "Tahun …"); sisanya jadi kartu angka utama
            $utama = [];
            $grup = [];
            foreach ($laporan['ringkasan'] as $label => $nilai) {
                $awalan = \Illuminate\Support\Str::before($label, ' ');
                if (in_array($awalan, ['Kondisi', 'Status', 'Tahun'], true) && $awalan !== $label) {
                    $grup[$awalan][\Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::after($label, ' '))] = $nilai;
                } else {
                    $utama[$label] = $nilai;
                }
            }
            $judulGrup = ['Kondisi' => 'Per Kondisi', 'Status' => 'Per Status', 'Tahun' => 'Per Tahun Pembelian'];
        @endphp
        <div class="space-y-4 border-b bg-marian-50/50 p-4">
            <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-semibold text-marian-600 ring-1 ring-marian-100">
                Periode <span class="text-marian-900">{{ $laporan['periode'] }}</span>
            </p>

            @if ($utama)
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fill,minmax(min(100%,11rem),1fr))]">
                    @foreach ($utama as $label => $nilai)
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-marian-100">
                            <p class="text-xs font-medium text-marian-500">{{ $label }}</p>
                            <p class="mt-1 font-extrabold text-marian-900 {{ is_numeric($nilai) ? 'text-2xl' : 'text-sm leading-snug' }}">{{ $nilai }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($grup)
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
                    @foreach ($grup as $awalan => $isi)
                        <section class="rounded-2xl bg-white p-4 ring-1 ring-marian-100">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-marian-500">{{ $judulGrup[$awalan] }}</h3>
                            <dl class="mt-2 divide-y divide-marian-50 text-sm">
                                @foreach ($isi as $label => $nilai)
                                    <div class="flex items-baseline justify-between gap-3 py-1.5">
                                        <dt class="text-marian-600">{{ $label }}</dt>
                                        <dd class="text-right font-bold {{ in_array(strtolower($label), ['rusak', 'hilang'], true) && $nilai ? 'text-red-600' : 'text-marian-900' }}">{{ $nilai }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endforeach
                </div>
            @endif
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
