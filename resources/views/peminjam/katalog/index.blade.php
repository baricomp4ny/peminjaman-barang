<x-app-layout>
    {{-- ================= HERO: putih di atas grid, gambar teknik di kanan ================= --}}
    <section class="panel grid items-stretch [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
        <div class="flex flex-col justify-center p-6 sm:p-10 lg:p-12">
            <div class="label-bagian">00 / {{ config('peminjaman.mitra_singkat') }}</div>
            <h1 class="mt-5 font-semibold leading-[1.1] tracking-tight text-marian-950 [font-size:clamp(2rem,4.5vw,3.2rem)]">
                Pinjam alat lab kultur jaringan<span class="text-marian-800">.</span>
            </h1>
            <p class="mt-4 max-w-xl text-marian-950/70 [font-size:clamp(.95rem,1.4vw,1.05rem)]">
                Cek ketersediaan alat secara langsung, ajukan peminjaman tanpa perlu akun, lalu ambil alat cukup dengan menunjukkan QR ke petugas lab.
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <x-link-button :href="route('peminjam.ajuan.create')" class="px-6 py-3 text-sm">Ajukan Peminjaman →</x-link-button>
                <x-link-button variant="secondary" href="#katalog" class="px-6 py-3 text-sm">Lihat Katalog</x-link-button>
            </div>

            {{-- Statistik singkat, angka mono seperti pembacaan alat ukur --}}
            <dl class="mt-10 grid max-w-lg grid-cols-3 border border-marian-950/15">
                @foreach ([['Jenis alat', $statistik['jenis']], ['Unit siap hari ini', $statistik['tersedia']], ['Kategori', $kategori->count()]] as $i => [$label, $nilai])
                    <div class="px-3 py-3 {{ $i > 0 ? 'border-l border-marian-950/15' : '' }}">
                        <dt class="text-[11px] leading-tight text-marian-950/60">{{ $label }}</dt>
                        <dd class="mt-1 font-mono text-2xl font-semibold text-marian-900">{{ str_pad($nilai, 2, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="flex items-center justify-center border-t border-marian-950/15 p-6 sm:p-10 md:border-l md:border-t-0">
            @if ($adaFoto)
                <img src="{{ asset('images/hero-lab.jpg') }}" alt="Laboratorium Kultur Jaringan" class="aspect-[5/4] w-full border border-marian-950/15 object-cover">
            @else
                <x-ilustrasi-lab class="max-w-md" />
            @endif
        </div>
    </section>

    {{-- ================= FITUR: empat sel bergaris ================= --}}
    <section class="mt-8 grid border-l border-t border-marian-950/15 bg-white [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
        @foreach ([
            ['Ajukan Online', 'Tanpa akun — cukup isi identitas & pilih alat.', 'M12 4v16m8-8H4'],
            ['Stok Real-time', 'Sisa unit dihitung sesuai tanggal pinjam Anda.', 'M4 19V9m6 10V5m6 14v-7m4 7H2'],
            ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman ke petugas.', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 18h2v2h-2zM14 18h2'],
            ['Pengingat Email', 'Kabar persetujuan & pengingat H-1 pengembalian.', 'M4 6h16v12H4zM4 7l8 6 8-6'],
        ] as $i => [$judul, $teks, $ikon])
            <div class="border-b border-r border-marian-950/15 p-5">
                <div class="flex items-center justify-between">
                    <svg class="h-6 w-6 text-marian-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="square"><path d="{{ $ikon }}" /></svg>
                    <span class="font-mono text-[11px] text-marian-950/40">0{{ $i + 1 }}</span>
                </div>
                <h3 class="mt-4 text-sm font-semibold">{{ $judul }}</h3>
                <p class="mt-1 text-xs leading-relaxed text-marian-950/65">{{ $teks }}</p>
            </div>
        @endforeach
    </section>

    {{-- ================= ATURAN + CEK STATUS ================= --}}
    <section class="mt-8 grid gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,20rem),1fr))]">
        <div class="panel border-l-4 border-l-marian-800 p-6 sm:p-8">
            <div class="label-bagian">01 / Aturan Peminjaman</div>
            <table class="mt-5 w-full text-sm">
                <tbody class="divide-y divide-marian-950/10">
                    <tr><td class="py-2.5 text-marian-950/70">Lama pinjam maksimal</td><td class="py-2.5 text-right font-mono font-semibold">{{ $aturan['lama'] }} hari</td></tr>
                    <tr><td class="py-2.5 text-marian-950/70">Unit per ajuan</td><td class="py-2.5 text-right font-mono font-semibold">maks. {{ $aturan['unit'] }}</td></tr>
                    <tr>
                        <td class="py-2.5 text-marian-950/70">Denda keterlambatan</td>
                        <td class="py-2.5 text-right font-mono font-semibold">{{ $aturan['denda'] > 0 ? rupiah($aturan['denda']).' /unit/hari' : 'tanpa denda' }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-4 text-xs text-marian-950/60">Alat hanya untuk kegiatan di laboratorium. Bawa kartu identitas (KTM) saat mengambil alat.</p>
        </div>

        <div class="panel flex flex-col justify-center p-6 sm:p-8">
            <div class="label-bagian">02 / Sudah mengajukan?</div>
            <p class="mt-4 text-sm leading-relaxed text-marian-950/75">
                Pantau status ajuan, lihat batas waktu pengambilan, dan tampilkan QR pengambilan dengan memasukkan <strong>kode peminjaman</strong> dan <strong>NIM</strong> Anda.
            </p>
            <div class="mt-5 flex flex-wrap items-center gap-4">
                <x-link-button :href="route('peminjam.status.form')" class="px-5 py-2.5 text-sm">Cek Status →</x-link-button>
                <a href="{{ route('peminjam.ajuan.create') }}" class="text-sm font-medium text-marian-800 underline decoration-marian-300 underline-offset-4 hover:text-marian-950">Belum? Ajukan sekarang</a>
            </div>
        </div>
    </section>

    {{-- ================= KATEGORI: daftar bernomor ================= --}}
    <section class="mt-14">
        <div class="label-bagian">03 / Kategori Alat</div>
        <h2 class="mt-2 font-semibold tracking-tight [font-size:clamp(1.4rem,2.6vw,1.9rem)]">Pilih kategori</h2>

        <div class="mt-5 grid border-l border-t border-marian-950/15 bg-white [grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))]">
            @foreach ($kategori as $i => $k)
                <a href="{{ route('peminjam.katalog.index', ['kategori_id' => $k->id]) }}#katalog"
                   class="group flex flex-col border-b border-r border-marian-950/15 p-5 transition hover:bg-marian-50">
                    <div class="flex items-start justify-between gap-3">
                        <span class="font-mono text-xs text-marian-950/45">K{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <x-ikon-alat :nama="$k->nama" class="h-8 w-8 text-marian-800" />
                    </div>
                    <h3 class="mt-3 font-semibold leading-snug">{{ $k->nama }}</h3>
                    <ul class="mt-2 space-y-0.5 text-xs text-marian-950/60">
                        @foreach ($k->barang->take(4) as $b)
                            <li>— {{ $b->nama }}</li>
                        @endforeach
                    </ul>
                    <span class="mt-auto flex items-center justify-between pt-4 font-mono text-[11px] text-marian-800">
                        <span>{{ $k->barang_count }} jenis</span>
                        <span class="transition group-hover:translate-x-1">→</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ================= KATALOG ================= --}}
    <section id="katalog" class="mt-14 scroll-mt-24">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="label-bagian">04 / Katalog Alat</div>
                <h2 class="mt-2 font-semibold tracking-tight [font-size:clamp(1.4rem,2.6vw,1.9rem)]">Daftar alat</h2>
                <p class="mt-1 text-sm text-marian-950/65">Angka "tersedia" adalah sisa unit untuk hari ini. Ketersediaan tanggal lain dicek saat mengisi form.</p>
            </div>
            <form method="GET" action="{{ route('peminjam.katalog.index') }}#katalog" class="flex w-full items-stretch border border-marian-950/20 bg-white sm:w-auto">
                @if (request('kategori_id'))<input type="hidden" name="kategori_id" value="{{ request('kategori_id') }}">@endif
                <input name="q" value="{{ request('q') }}" placeholder="Cari alat…" aria-label="Cari alat"
                       class="min-w-0 flex-1 border-0 bg-transparent text-sm placeholder:text-marian-950/35 focus:ring-0 sm:w-60">
                <button class="border-l border-marian-950/20 bg-marian-800 px-4 text-sm font-semibold text-white hover:bg-marian-900">Cari</button>
            </form>
        </div>

        {{-- Filter kategori: tab bergaris bawah --}}
        <div class="mt-5 flex overflow-x-auto border-b border-marian-950/15 [scrollbar-width:none]">
            @php $semua = ! request('kategori_id'); @endphp
            <a href="{{ route('peminjam.katalog.index', array_filter(['q' => request('q')])) }}#katalog"
               class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $semua ? 'border-marian-800 text-marian-900' : 'border-transparent text-marian-950/60 hover:text-marian-900' }}">Semua</a>
            @foreach ($kategori as $k)
                @php $aktif = (string) request('kategori_id') === (string) $k->id; @endphp
                <a href="{{ route('peminjam.katalog.index', array_filter(['kategori_id' => $k->id, 'q' => request('q')])) }}#katalog"
                   class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition {{ $aktif ? 'border-marian-800 text-marian-900' : 'border-transparent text-marian-950/60 hover:text-marian-900' }}">{{ $k->nama }}</a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 [grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))]">
            @forelse ($barang as $b)
                <x-kartu-alat :barang="$b" :sisa="$stok[$b->id]['sisa'] ?? 0" />
            @empty
                <div class="panel col-span-full p-10 text-center text-marian-950/65">
                    Alat tidak ditemukan. <a href="{{ route('peminjam.katalog.index') }}#katalog" class="font-semibold text-marian-800 underline">Tampilkan semua</a>
                </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $barang->fragment('katalog')->links() }}</div>
    </section>

    {{-- ================= ALUR: garis waktu bernomor ================= --}}
    <section id="alur" class="panel mt-14 scroll-mt-24 p-6 sm:p-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="label-bagian">05 / Cara Meminjam</div>
                <h2 class="mt-2 font-semibold tracking-tight [font-size:clamp(1.4rem,2.6vw,1.9rem)]">Dari ajuan sampai pengembalian</h2>
            </div>
            <x-link-button :href="route('peminjam.ajuan.create')" class="px-5 py-2.5 text-sm">Mulai ajukan →</x-link-button>
        </div>

        <ol class="mt-8 grid gap-0 [grid-template-columns:repeat(auto-fit,minmax(min(100%,13rem),1fr))]">
            @foreach ([
                ['Ajukan', 'Isi identitas, pilih alat & tanggal. Kode PJM dikirim ke email.'],
                ['Diverifikasi', 'Petugas mengecek identitas, stok, dan aturan. Kabar dikirim lewat email.'],
                ['Ambil dengan QR', 'Tunjukkan QR kode peminjaman + KTM. Petugas memindai setiap unit.'],
                ['Kembalikan', 'Kembalikan tepat waktu. Terlambat dikenai denda per unit per hari.'],
            ] as $i => [$judul, $teks])
                <li class="border-t-2 border-marian-800 pb-4 pr-5 pt-4">
                    <span class="font-mono text-sm font-semibold text-marian-800">Langkah {{ $i + 1 }}</span>
                    <h3 class="mt-2 font-semibold">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-marian-950/65">{{ $teks }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</x-app-layout>
