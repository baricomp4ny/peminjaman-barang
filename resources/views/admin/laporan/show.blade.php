@use('Illuminate\Support\Str')
@use('App\Services\LaporanService')

@php
    $web = $laporan['web'];
    $q = request()->only(['dari', 'sampai']);
    $unduh = fn ($format) => route('admin.laporan.show', array_merge(['jenis' => $laporan['jenis'], 'format' => $format], $q));

    // Kolom grid baris: kolom utama + kolom panah buka/tutup
    $gridKolom = implode(' ', $web['utama']).' 2.5rem';
    // Kolom yang tidak tampil di baris masuk ke panel detail (kolom "No" tidak perlu)
    $detail = collect($laporan['kolom'])->keys()->reject(fn ($i) => isset($web['utama'][$i]) || $laporan['kolom'][$i] === 'No')->values();

    // Warna pil sesuai arti nilainya; merah hanya untuk masalah
    $pil = function ($nilai) use ($laporan) {
        $teks = Str::lower((string) $nilai);

        return match (true) {
            in_array($teks, ['rusak', 'hilang', 'belum kembali'], true), Str::contains($teks, 'berjalan') => 'bg-red-50 text-red-700',
            $teks === 'perbaikan' => 'bg-gold/30 text-marian-900',
            $teks === 'dipinjam', $teks === 'paling sering' => 'bg-marian-800 text-white',
            $teks === 'tidak pernah dipinjam', $teks === 'paling jarang' => 'bg-marian-100 text-marian-600',
            is_numeric($nilai) && (int) $nilai > 0 && $laporan['jenis'] !== 'statistik' => 'bg-red-50 text-red-700',
            default => 'bg-marian-50 text-marian-700',
        };
    };

    // Ringkasan lengkap dikelompokkan per awalan ("Kondisi …", "Status …", "Tahun …") untuk bagian lipat
    $grup = [];
    foreach ($laporan['ringkasan'] as $label => $nilai) {
        $awalan = Str::before($label, ' ');
        $kunci = in_array($awalan, ['Kondisi', 'Status', 'Tahun'], true) && $awalan !== $label ? $awalan : 'Ringkasan';
        $grup[$kunci][$kunci === 'Ringkasan' ? $label : Str::ucfirst(Str::after($label, ' '))] = $nilai;
    }
    $judulGrup = ['Ringkasan' => 'Ringkasan', 'Kondisi' => 'Per Kondisi', 'Status' => 'Per Status', 'Tahun' => 'Per Tahun Pembelian'];
@endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Laporan</span></x-slot>

    {{-- 1. Kartu angka --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
        @foreach ($web['kartu'] as $i => [$label, $nilai, $perhatian])
            <x-admin.kartu-angka :label="$label" :nilai="$nilai" :perhatian="$perhatian" :urutan="$i" />
        @endforeach
    </div>

    {{-- Rincian ringkasan (dilipat supaya halaman tetap bersih) --}}
    <details class="group mt-3">
        <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-xs font-bold uppercase tracking-wider text-marian-600 hover:text-marian-900">
            Rincian ringkasan <span class="transition group-open:rotate-180">▾</span>
        </summary>
        <div class="mt-3 grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))]">
            @foreach ($grup as $kunci => $isi)
                <section class="rounded-2xl bg-white p-4 shadow-lembut ring-1 ring-marian-100">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-marian-500">{{ $judulGrup[$kunci] }}</h3>
                    <dl class="mt-2 divide-y divide-marian-50 text-sm">
                        @foreach ($isi as $label => $nilai)
                            <div class="flex items-baseline justify-between gap-3 py-1.5">
                                <dt class="text-marian-600">{{ $label }}</dt>
                                <dd class="text-right font-bold {{ in_array(Str::lower($label), ['rusak', 'hilang'], true) && $nilai ? 'text-red-600' : 'text-marian-900' }}">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </details>

    {{-- 2. Judul + filter dalam satu baris --}}
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-light uppercase tracking-wide text-marian-900 [font-size:clamp(1.4rem,2.6vw,2rem)]">{{ $laporan['judul'] }}</h1>
            <p class="text-xs text-marian-500">{{ $laporan['periode'] }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-marian-500">
            <label class="flex items-center gap-2">Jenis
                <select onchange="window.location = this.value" aria-label="Pilih jenis laporan"
                        class="rounded-full border-0 bg-white py-2 pe-8 ps-4 text-[11px] font-bold uppercase tracking-wider text-marian-700 shadow-lembut ring-1 ring-marian-100 focus:ring-marian-400">
                    @foreach (LaporanService::JENIS as $kunci => $judul)
                        <option value="{{ route('admin.laporan.show', array_merge(['jenis' => $kunci], $q)) }}" @selected($kunci === $laporan['jenis'])>{{ Str::after($judul, 'Laporan ') }}</option>
                    @endforeach
                </select>
            </label>

            @if ($laporan['jenis'] !== 'inventaris')
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <span>Periode</span>
                    <input type="date" name="dari" value="{{ $dari }}" aria-label="Dari tanggal"
                           class="rounded-full border-0 bg-white px-4 py-2 text-xs font-bold text-marian-700 shadow-lembut ring-1 ring-marian-100 focus:ring-marian-400">
                    <span>–</span>
                    <input type="date" name="sampai" value="{{ $sampai }}" aria-label="Sampai tanggal"
                           class="rounded-full border-0 bg-white px-4 py-2 text-xs font-bold text-marian-700 shadow-lembut ring-1 ring-marian-100 focus:ring-marian-400">
                    <button class="rounded-full bg-white px-4 py-2 font-bold text-marian-800 shadow-lembut ring-1 ring-marian-200 hover:bg-marian-50">Tampilkan</button>
                </form>
            @endif

            <a href="{{ $unduh('pdf') }}" class="rounded-full bg-white px-4 py-2 font-bold text-marian-800 ring-1 ring-marian-200 hover:bg-marian-50">PDF</a>
            <a href="{{ $unduh('excel') }}" class="rounded-full bg-marian-800 px-4 py-2 font-bold text-white hover:bg-marian-900">Excel</a>
        </div>
    </div>
    <x-input-error :messages="$errors->all()" class="mt-2" />

    {{-- 3. Tabel bergaya baris kartu; klik baris untuk membuka detail --}}
    <div class="mt-4" style="--kolom: {{ $gridKolom }}">
        <div class="hidden px-5 text-[11px] font-semibold uppercase tracking-wider text-marian-400 md:grid md:[grid-template-columns:var(--kolom)] md:gap-3">
            @foreach ($web['utama'] as $i => $lebar)
                <span>{{ $laporan['kolom'][$i] }}</span>
            @endforeach
            <span></span>
        </div>

        <div class="mt-2 space-y-2">
            @forelse ($laporan['baris'] as $n => $baris)
                <div x-data="{ buka: false }" class="overflow-hidden rounded-2xl bg-white shadow-lembut ring-1 transition" :class="buka ? 'ring-marian-200' : 'ring-transparent'">
                    <button type="button" @click="buka = ! buka" :aria-expanded="buka"
                            class="grid w-full grid-cols-2 items-center gap-x-3 gap-y-1 border-l-4 px-5 py-3.5 text-left text-sm transition hover:bg-marian-50/60 md:[grid-template-columns:var(--kolom)]"
                            :class="buka ? 'border-marian-800' : 'border-transparent'">
                        @foreach ($web['utama'] as $i => $lebar)
                            <span class="min-w-0">
                                {{-- Di HP label kolom tampil kecil di atas nilai --}}
                                <span class="block text-[10px] uppercase tracking-wider text-marian-400 md:hidden">{{ $laporan['kolom'][$i] }}</span>
                                @if (in_array($i, $web['pil'], true))
                                    <b class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $pil($baris[$i]) }}">{{ $baris[$i] }}</b>
                                @elseif ($loop->first)
                                    <span class="font-mono font-semibold text-marian-900">{{ $baris[$i] }}</span>
                                @else
                                    <span class="{{ $loop->index === 1 ? 'font-semibold text-marian-900' : 'text-marian-600' }} break-words">{{ $baris[$i] }}</span>
                                @endif
                            </span>
                        @endforeach
                        <span class="col-span-2 text-right text-marian-400 md:col-span-1" aria-hidden="true">
                            <span class="inline-block transition" :class="buka && 'rotate-180 text-marian-800'">⌄</span>
                        </span>
                    </button>

                    <div x-show="buka" x-transition.opacity style="display: none;" class="border-t border-marian-100 bg-marian-50 px-5 py-4 text-xs sm:px-8">
                        <dl class="grid gap-x-8 gap-y-1.5 [grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr))]">
                            @foreach ($detail as $i)
                                <div class="grid grid-cols-[8rem_1fr] gap-2">
                                    <dt class="text-marian-500">{{ $laporan['kolom'][$i] }}</dt>
                                    <dd class="font-semibold text-marian-900">{{ $baris[$i] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if (! empty($web['aksi'][$n]))
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                @foreach ($web['aksi'][$n] as $aksi)
                                    @if (($aksi[2] ?? null) === 'wa')
                                        <a href="{{ $aksi[1] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-[#25D366] px-3 py-1.5 font-bold text-white hover:brightness-95">WhatsApp</a>
                                    @else
                                        <a href="{{ $aksi[1] }}" class="font-bold text-marian-800 underline-offset-2 hover:underline">{{ $aksi[0] }} →</a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="rounded-2xl bg-white p-8 text-center text-sm text-marian-500 shadow-lembut">Tidak ada data pada periode ini.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
