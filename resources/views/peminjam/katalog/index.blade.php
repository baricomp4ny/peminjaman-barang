<x-app-layout>
    {{-- ================= HERO ================= --}}
    <section class="relative">
        {{-- "Tab" putih di kiri atas & lekukan bulat di kanan bawah meniru gaya panel referensi --}}
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
        {{-- lekukan bulat kanan bawah --}}
        <div aria-hidden="true" class="absolute -bottom-6 right-10 hidden h-20 w-20 rounded-full bg-marian-50 ring-8 ring-marian-50 sm:block lg:right-24 lg:h-28 lg:w-28"></div>
    </section>

    {{-- ================= FITUR ================= --}}
    <section class="mt-12 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
        @foreach ([
            ['Ajukan Online', 'Tanpa akun — cukup isi identitas & pilih alat.', 'M12 4v16m8-8H4'],
            ['Stok Real-time', 'Sisa unit dihitung sesuai tanggal pinjam Anda.', 'M4 19V9m6 10V5m6 14v-7m4 7H2'],
            ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman ke petugas.', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2zM14 18h2'],
            ['Pengingat Email', 'Kabar persetujuan & pengingat H-1 pengembalian.', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        ] as [$judul, $teks, $ikon])
            <div class="flex flex-col items-center rounded-4xl bg-white px-5 py-6 text-center shadow-lembut ring-1 ring-marian-100">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-marian-100 to-marian-200 text-marian-800">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $ikon }}" /></svg>
                </span>
                <h3 class="mt-3 text-sm font-bold text-marian-900">{{ $judul }}</h3>
                <p class="mt-1 text-xs leading-relaxed text-marian-600">{{ $teks }}</p>
            </div>
        @endforeach
    </section>

    {{-- ================= ATURAN + AJAKAN (baris dua kartu) ================= --}}
    <section class="mt-6 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,20rem),1fr))]">
        <div class="relative overflow-hidden rounded-4xl bg-gradient-to-br from-marian-800 to-marian-600 p-6 text-white sm:p-8">
            <div aria-hidden="true" class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
            <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">Aturan Peminjaman</span>
            <ul class="mt-5 grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,8rem),1fr))]">
                <li class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/15"><div class="text-3xl font-extrabold">{{ $aturan['lama'] }}</div><div class="text-xs text-marian-100">hari maksimal</div></li>
                <li class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/15"><div class="text-3xl font-extrabold">{{ $aturan['unit'] }}</div><div class="text-xs text-marian-100">unit per ajuan</div></li>
                <li class="rounded-3xl bg-white/10 p-4 ring-1 ring-white/15">
                    <div class="text-2xl font-extrabold">{{ $aturan['denda'] > 0 ? 'Rp'.number_format($aturan['denda'], 0, ',', '.') : 'Gratis' }}</div>
                    <div class="text-xs text-marian-100">{{ $aturan['denda'] > 0 ? 'denda/unit/hari' : 'tanpa denda' }}</div>
                </li>
            </ul>
            <p class="mt-4 text-xs text-marian-100">Alat hanya untuk kegiatan di laboratorium. Bawa kartu identitas (KTM) saat mengambil alat.</p>
        </div>

        <div class="flex flex-col justify-center rounded-4xl bg-white p-6 shadow-lembut ring-1 ring-marian-100 sm:p-8">
            <span class="w-fit rounded-full bg-marian-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-marian-700 ring-1 ring-marian-200">Sudah mengajukan?</span>
            <p class="mt-4 text-sm leading-relaxed text-marian-700">
                Pantau status ajuan, lihat batas waktu pengambilan, dan tampilkan QR pengambilan dengan memasukkan <strong>kode peminjaman</strong> dan <strong>NIM</strong> Anda.
            </p>
            <div class="mt-5 flex flex-wrap items-center gap-4">
                <a href="{{ route('peminjam.status.form') }}" class="inline-flex items-center gap-2 rounded-full bg-marian-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-marian-900">
                    Cek Status
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
                <a href="{{ route('peminjam.ajuan.create') }}" class="text-sm font-semibold text-marian-700 underline decoration-marian-300 underline-offset-4 hover:text-marian-900">Belum? Ajukan sekarang</a>
            </div>
        </div>
    </section>

    {{-- ================= KATEGORI (bento) ================= --}}
    <section class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-extrabold text-marian-900 [font-size:clamp(1.4rem,2.6vw,2rem)]">Kategori Alat</h2>
                <p class="text-sm text-marian-600">Pilih kategori untuk melihat alat yang tersedia.</p>
            </div>
        </div>

        <div class="mt-5 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12.5rem),1fr))]">
            @foreach ($kategori as $i => $k)
                <a href="{{ route('peminjam.katalog.index', ['kategori_id' => $k->id]) }}#katalog"
                   class="group relative flex min-h-[13rem] flex-col overflow-hidden rounded-4xl p-6 shadow-lembut ring-1 transition hover:-translate-y-1
                          {{ $i % 3 === 1 ? 'bg-marian-100 ring-marian-200' : 'bg-white ring-marian-100' }}">
                    <span class="w-fit rounded-full bg-marian-800 px-3 py-1 text-[11px] font-semibold text-white">{{ $k->barang_count }} jenis</span>
                    <h3 class="mt-3 max-w-[85%] text-lg font-extrabold leading-snug text-marian-900">{{ $k->nama }}</h3>
                    <ul class="mt-2 space-y-0.5 text-xs text-marian-600">
                        @foreach ($k->barang->take(4) as $b)
                            <li>{{ $b->nama }}</li>
                        @endforeach
                    </ul>
                    <span class="mt-auto inline-flex items-center gap-1 pt-4 text-xs font-bold text-marian-800">
                        Lihat semua <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </span>
                    <x-ikon-alat :nama="$k->nama" class="absolute -bottom-3 -right-3 h-28 w-28 text-marian-200 transition group-hover:text-marian-300" />
                </a>
            @endforeach

            {{-- Kartu ajakan bergradasi --}}
            <a href="#alur" class="relative flex min-h-[13rem] flex-col items-center justify-center overflow-hidden rounded-4xl bg-gradient-to-br from-marian-900 to-marian-600 p-6 text-center text-white shadow-lembut transition hover:-translate-y-1">
                <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/25">Panduan</span>
                <span class="mt-3 text-2xl font-extrabold">Cara Meminjam</span>
                <span class="mt-1 text-xs text-marian-100">4 langkah dari ajuan sampai pengembalian</span>
                <span aria-hidden="true" class="absolute -bottom-12 -left-12 h-32 w-32 rounded-full bg-white/10"></span>
            </a>
        </div>
    </section>

    {{-- ================= KATALOG ================= --}}
    <section id="katalog" class="mt-14 scroll-mt-28">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-marian-900 [font-size:clamp(1.4rem,2.6vw,2rem)]">Katalog Alat</h2>
                <p class="text-sm text-marian-600">Angka "tersedia" adalah sisa unit untuk hari ini. Ketersediaan tanggal lain dicek saat mengisi form.</p>
            </div>
            <form method="GET" action="{{ route('peminjam.katalog.index') }}#katalog" class="flex w-full items-center gap-2 rounded-full bg-white p-1.5 shadow-lembut ring-1 ring-marian-100 sm:w-auto">
                @if (request('kategori_id'))<input type="hidden" name="kategori_id" value="{{ request('kategori_id') }}">@endif
                <svg class="ms-3 h-5 w-5 shrink-0 text-marian-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Cari alat…" aria-label="Cari alat"
                       class="min-w-0 flex-1 border-0 bg-transparent text-sm text-marian-900 placeholder:text-marian-400 focus:ring-0 sm:w-56">
                <button class="rounded-full bg-marian-800 px-4 py-2 text-sm font-semibold text-white hover:bg-marian-900">Cari</button>
            </form>
        </div>

        {{-- Filter kategori berbentuk tab pil --}}
        <div class="mt-5 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
            @php $semua = ! request('kategori_id'); @endphp
            <a href="{{ route('peminjam.katalog.index', array_filter(['q' => request('q')])) }}#katalog"
               class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $semua ? 'bg-marian-800 text-white' : 'bg-white text-marian-700 ring-1 ring-marian-100 hover:bg-marian-50' }}">Semua</a>
            @foreach ($kategori as $k)
                @php $aktif = (string) request('kategori_id') === (string) $k->id; @endphp
                <a href="{{ route('peminjam.katalog.index', array_filter(['kategori_id' => $k->id, 'q' => request('q')])) }}#katalog"
                   class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition {{ $aktif ? 'bg-marian-800 text-white' : 'bg-white text-marian-700 ring-1 ring-marian-100 hover:bg-marian-50' }}">{{ $k->nama }}</a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))]">
            @forelse ($barang as $b)
                <x-kartu-alat :barang="$b" :sisa="$stok[$b->id]['sisa'] ?? 0" />
            @empty
                <div class="col-span-full rounded-4xl bg-white p-10 text-center text-marian-600 shadow-lembut ring-1 ring-marian-100">
                    Alat tidak ditemukan. <a href="{{ route('peminjam.katalog.index') }}#katalog" class="font-semibold text-marian-800 underline">Tampilkan semua</a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $barang->fragment('katalog')->links() }}</div>
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

        <ol class="mt-8 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
            @foreach ([
                ['Ajukan', 'Isi identitas, pilih alat & tanggal. Kode PJM dikirim ke email.'],
                ['Diverifikasi', 'Petugas mengecek identitas, stok, dan aturan. Kabar dikirim lewat email.'],
                ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman + KTM. Petugas memindai setiap unit.'],
                ['Kembalikan', 'Kembalikan tepat waktu. Terlambat dikenai denda per unit per hari.'],
            ] as $i => [$judul, $teks])
                <li class="relative rounded-4xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-base font-extrabold text-marian-900">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-bold">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-marian-100">{{ $teks }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</x-app-layout>
