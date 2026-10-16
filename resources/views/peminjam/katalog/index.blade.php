<x-app-layout>
    {{-- ================= HERO ================= --}}
    <section class="relative">
        {{-- Panel hero bergradasi --}}
        <div class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 px-6 pb-14 pt-10 text-white sm:px-10 lg:px-14 lg:pt-14">
            <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 left-1/3 h-80 w-80 rounded-full bg-marian-400/20 blur-2xl"></div>

            <div class="relative grid items-center gap-10 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold tracking-wide ring-1 ring-white/25">
                        <span class="h-2 w-2 rounded-full bg-marian-200"></span> {{ config('peminjaman.mitra_singkat') }}
                    </span>
                    <h1 class="mt-5 font-extrabold leading-tight [font-size:clamp(2rem,4.5vw,3.4rem)]">
                        Pinjam Alat Lab<br class="hidden sm:block"> Kultur Jaringan dengan Mudah
                    </h1>
                    <p class="mt-4 max-w-xl text-marian-100 [font-size:clamp(.95rem,1.4vw,1.1rem)]">
                        Cek ketersediaan alat secara langsung, ajukan peminjaman tanpa perlu akun, lalu ambil alat cukup dengan menunjukkan QR ke petugas lab.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="{{ route('peminjam.ajuan.create') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-marian-900 shadow-lg transition hover:bg-marian-100">
                            Ajukan Peminjaman
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </a>
                        <a href="#katalog" class="inline-flex items-center rounded-full px-6 py-3 text-sm font-bold text-white ring-1 ring-white/50 transition hover:bg-white/10">Lihat Katalog</a>
                    </div>

                    {{-- Statistik singkat --}}
                    <dl class="mt-9 grid max-w-lg grid-cols-3 gap-3">
                        @foreach ([['Jenis alat', $statistik['jenis']], ['Unit siap hari ini', $statistik['tersedia']], ['Kategori', $kategori->count()]] as [$label, $nilai])
                            <div class="rounded-3xl bg-white/10 px-3 py-3 ring-1 ring-white/15 backdrop-blur">
                                <dt class="text-[11px] leading-tight text-marian-200 sm:text-xs">{{ $label }}</dt>
                                <dd class="mt-1 text-2xl font-extrabold">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="mx-auto w-full max-w-md lg:max-w-lg">
                    @if ($adaFoto)
                        <img src="{{ asset('images/hero-lab.jpg') }}" alt="Laboratorium Kultur Jaringan" class="aspect-[5/4] w-full rounded-5xl object-cover shadow-2xl ring-4 ring-white/20">
                    @else
                        <x-ilustrasi-lab class="drop-shadow-2xl" />
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FITUR (tanpa kartu: ikon + teks) ================= --}}
    <section class="mt-12 grid gap-x-6 gap-y-8 px-2 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
        @foreach ([
            ['Ajukan Online', 'Tanpa akun — cukup isi identitas & pilih alat.', 'M12 4v16m8-8H4'],
            ['Stok Real-time', 'Sisa unit dihitung sesuai tanggal pinjam Anda.', 'M4 19V9m6 10V5m6 14v-7m4 7H2'],
            ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman ke petugas.', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2zM14 18h2'],
            ['Pengingat Email', 'Kabar persetujuan & pengingat H-1 pengembalian.', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        ] as [$judul, $teks, $ikon])
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-marian-100 to-marian-200 text-marian-800">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $ikon }}" /></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-marian-900">{{ $judul }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-marian-600">{{ $teks }}</p>
                </div>
            </div>
        @endforeach
    </section>

    {{-- ================= ATURAN + CEK STATUS (satu pita) ================= --}}
    <section class="relative mt-12 overflow-hidden rounded-4xl bg-gradient-to-br from-marian-800 to-marian-600 p-6 text-white sm:p-8">
        <div aria-hidden="true" class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-8">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-marian-200">Aturan Peminjaman</span>
                {{-- Angka aturan dipisah garis tipis, bukan kotak --}}
                <dl class="mt-3 flex flex-wrap divide-x divide-white/20">
                    <div class="pr-6"><dd class="text-3xl font-extrabold">{{ $aturan['lama'] }}</dd><dt class="text-xs text-marian-100">hari maksimal</dt></div>
                    <div class="px-6"><dd class="text-3xl font-extrabold">{{ $aturan['unit'] }}</dd><dt class="text-xs text-marian-100">unit per ajuan</dt></div>
                    <div class="pl-6">
                        <dd class="text-3xl font-extrabold">{{ $aturan['denda'] > 0 ? rupiah($aturan['denda']) : 'Gratis' }}</dd>
                        <dt class="text-xs text-marian-100">{{ $aturan['denda'] > 0 ? 'denda/unit/hari' : 'tanpa denda' }}</dt>
                    </div>
                </dl>
                <p class="mt-4 max-w-xl text-xs text-marian-100">Alat hanya untuk kegiatan di laboratorium. Bawa kartu identitas (KTM) saat mengambil alat.</p>
            </div>
            <div class="max-w-sm">
                <p class="text-sm text-marian-100">Sudah mengajukan? Pantau status dan tampilkan QR pengambilan dengan <strong class="text-white">kode peminjaman</strong> + <strong class="text-white">NIM</strong>.</p>
                <a href="{{ route('peminjam.status.form') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-marian-900 transition hover:bg-marian-100">
                    Cek Status
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ================= KATALOG: sidebar filter di kiri, grid alat di kanan ================= --}}
    @php
        // Tautan filter mempertahankan filter lain; halaman kembali ke 1
        $tautan = fn (array $ubah) => route('peminjam.katalog.index', array_filter(array_merge(request()->except('page'), $ubah), fn ($v) => $v !== null && $v !== '')).'#katalog';
        $label = 'block rounded-2xl px-3 py-2 text-sm transition';
    @endphp
    <section id="katalog" class="mt-14 scroll-mt-28" x-data="{ filter: false }">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-marian-900 [font-size:clamp(1.4rem,2.6vw,2rem)]">Katalog Alat</h2>
                <p class="text-sm text-marian-600">Pilih tanggal di filter untuk melihat sisa unit pada rentang pinjam Anda.</p>
            </div>
            <button type="button" @click="filter = ! filter" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-marian-800 shadow-lembut ring-1 ring-marian-100 lg:hidden" :aria-expanded="filter">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4" /></svg>
                Filter
            </button>
        </div>

        <div class="mt-6 grid items-start gap-6 lg:[grid-template-columns:17rem_minmax(0,1fr)]">
            {{-- ---------- SIDEBAR FILTER ---------- --}}
            <aside class="hidden space-y-6 rounded-4xl bg-white p-5 shadow-lembut ring-1 ring-marian-100 lg:sticky lg:top-28 lg:block" :class="filter && '!block'">
                <form method="GET" action="{{ route('peminjam.katalog.index') }}#katalog" class="space-y-6">
                    @foreach (['kategori_id', 'lokasi_id', 'urut'] as $tetap)
                        @if (request($tetap))<input type="hidden" name="{{ $tetap }}" value="{{ request($tetap) }}">@endif
                    @endforeach

                    {{-- Cari --}}
                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-marian-900">Cari Alat</h3>
                        <div class="mt-3 flex items-center rounded-full bg-marian-50 ring-1 ring-marian-100">
                            <input name="q" value="{{ request('q') }}" placeholder="Nama atau kode…" aria-label="Cari alat"
                                   class="min-w-0 flex-1 border-0 bg-transparent px-4 py-2.5 text-sm placeholder:text-marian-400 focus:ring-0">
                            <svg class="me-4 h-4 w-4 shrink-0 text-marian-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                        </div>
                    </div>

                    {{-- Cek tanggal: sisa unit dihitung untuk rentang ini --}}
                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-marian-900">Cek Tanggal</h3>
                        <div class="mt-3 grid gap-2">
                            <label class="text-xs text-marian-600">Tanggal pinjam
                                <x-text-input type="date" name="mulai" class="mt-1 block w-full text-sm" :value="request('mulai')" min="{{ today()->toDateString() }}" />
                            </label>
                            <label class="text-xs text-marian-600">Tanggal kembali
                                <x-text-input type="date" name="selesai" class="mt-1 block w-full text-sm" :value="request('selesai')" min="{{ today()->toDateString() }}" />
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('mulai')" class="mt-2" />
                        <x-input-error :messages="$errors->get('selesai')" class="mt-2" />
                        <label class="mt-3 flex items-center gap-2 text-sm text-marian-800">
                            <input type="checkbox" name="tersedia" value="1" @checked(request()->boolean('tersedia')) class="rounded border-marian-300 text-marian-800 focus:ring-marian-500">
                            Hanya yang tersedia
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button class="flex-1">Terapkan</x-primary-button>
                        @if ($adaFilter)
                            <a href="{{ route('peminjam.katalog.index') }}#katalog" class="text-sm font-semibold text-marian-600 hover:text-marian-900">Reset</a>
                        @endif
                    </div>
                </form>

                {{-- Kategori (tautan + jumlah jenis) --}}
                <div class="border-t border-marian-100 pt-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-marian-900">Kategori</h3>
                    <ul class="mt-3 space-y-1">
                        <li><a href="{{ $tautan(['kategori_id' => null]) }}" class="{{ $label }} {{ ! request('kategori_id') ? 'bg-marian-800 font-semibold text-white' : 'text-marian-700 hover:bg-marian-50' }}">Semua kategori</a></li>
                        @foreach ($kategori as $k)
                            @php $aktif = request('kategori_id') === $k->id; @endphp
                            <li>
                                <a href="{{ $tautan(['kategori_id' => $k->id]) }}" class="{{ $label }} flex justify-between gap-2 {{ $aktif ? 'bg-marian-800 font-semibold text-white' : 'text-marian-700 hover:bg-marian-50' }}">
                                    <span>{{ $k->nama }}</span><span class="{{ $aktif ? 'text-marian-200' : 'text-marian-400' }}">({{ $k->barang_count }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Lokasi --}}
                <div class="border-t border-marian-100 pt-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-marian-900">Lokasi</h3>
                    <ul class="mt-3 space-y-1">
                        <li><a href="{{ $tautan(['lokasi_id' => null]) }}" class="{{ $label }} {{ ! request('lokasi_id') ? 'bg-marian-800 font-semibold text-white' : 'text-marian-700 hover:bg-marian-50' }}">Semua lokasi</a></li>
                        @foreach ($lokasi as $l)
                            <li><a href="{{ $tautan(['lokasi_id' => $l->id]) }}" class="{{ $label }} {{ request('lokasi_id') === $l->id ? 'bg-marian-800 font-semibold text-white' : 'text-marian-700 hover:bg-marian-50' }}">{{ $l->nama }}</a></li>
                        @endforeach
                    </ul>
                </div>

                {{-- Paling sering dipinjam --}}
                @if ($populer->isNotEmpty())
                    <div class="border-t border-marian-100 pt-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-marian-900">Paling Sering Dipinjam</h3>
                        <ul class="mt-3 divide-y divide-marian-100">
                            @foreach ($populer as $b)
                                <li>
                                    <a href="{{ route('peminjam.katalog.show', $b) }}" class="flex items-center justify-between gap-3 py-2.5 hover:text-marian-800">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-marian-900">{{ $b->nama }}</span>
                                            <span class="text-xs text-marian-500">{{ $b->total_dipinjam }}× dipinjam</span>
                                        </span>
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-marian-50 text-marian-700 ring-1 ring-marian-100">
                                            @if ($b->foto)
                                                <img src="{{ Storage::url($b->foto) }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <x-ikon-alat :nama="$b->nama" class="h-6 w-6" />
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>

            {{-- ---------- GRID ALAT ---------- --}}
            <div>
                {{-- Bilah atas: keterangan tanggal sisa unit + urutan --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-marian-600">
                        Sisa unit {{ $rentang['dipilih'] ? $rentang['mulai']->translatedFormat('d M').' – '.$rentang['selesai']->translatedFormat('d M Y') : 'hari ini' }}
                    </p>
                    <form method="GET" action="{{ route('peminjam.katalog.index') }}#katalog">
                        @foreach (request()->except(['urut', 'page']) as $nama => $nilai)
                            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                        @endforeach
                        <x-select name="urut" class="py-2 text-sm" aria-label="Urutkan" onchange="this.form.submit()">
                            @foreach ($urutan as $nilai => $teks)
                                <option value="{{ $nilai }}" @selected(request('urut', 'nama') === $nilai)>{{ $teks }}</option>
                            @endforeach
                        </x-select>
                        <noscript><button class="ms-2 text-sm font-semibold text-marian-800">Urutkan</button></noscript>
                    </form>
                </div>

                <div class="mt-5 grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,14rem),1fr))]">
                    @forelse ($barang as $b)
                        <x-kartu-alat :barang="$b" :sisa="$stok[$b->id]['sisa'] ?? 0" />
                    @empty
                        <div class="col-span-full rounded-4xl bg-white p-10 text-center text-marian-600 shadow-lembut ring-1 ring-marian-100">
                            Alat tidak ditemukan. <a href="{{ route('peminjam.katalog.index') }}#katalog" class="font-semibold text-marian-800 underline">Tampilkan semua</a>
                        </div>
                    @endforelse
                </div>

                <div class="mt-8">{{ $barang->links('pagination.tanpa-ringkasan') }}</div>
            </div>
        </div>
    </section>

    {{-- ================= ALUR ================= --}}
    <section id="alur" class="mt-14 scroll-mt-28 overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-600 p-6 text-white sm:p-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/25">Cara Meminjam</span>
                <h2 class="mt-3 font-extrabold [font-size:clamp(1.4rem,2.6vw,2rem)]">Dari ajuan sampai pengembalian</h2>
            </div>
            <a href="{{ route('peminjam.ajuan.create') }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-bold text-marian-900 hover:bg-marian-100">Mulai ajukan</a>
        </div>

        <ol class="mt-8 grid gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
            @foreach ([
                ['Ajukan', 'Isi identitas, pilih alat & tanggal. Kode PJM dikirim ke email.'],
                ['Diverifikasi', 'Petugas mengecek identitas, stok, dan aturan. Kabar dikirim lewat email.'],
                ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman + KTM. Petugas memindai setiap unit.'],
                ['Kembalikan', 'Kembalikan tepat waktu. Terlambat dikenai denda per unit per hari.'],
            ] as $i => [$judul, $teks])
                <li class="relative border-t border-white/25 pt-5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-base font-extrabold text-marian-900">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-bold">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-marian-100">{{ $teks }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</x-app-layout>
