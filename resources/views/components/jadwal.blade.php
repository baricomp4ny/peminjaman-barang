@props(['data', 'mulai', 'tab', 'kategori', 'lokasi', 'publik' => false, 'rute'])

@php
    // Tautan yang mempertahankan filter lain
    $tautan = fn (array $ubah) => route($rute, array_filter(array_merge(request()->only(['mulai', 'kategori_id', 'lokasi_id', 'q', 'tab']), $ubah), fn ($v) => $v !== null && $v !== ''));
    $warna = [
        'dipinjam' => 'bg-marian-800 text-white',
        'terlambat' => 'bg-red-600 text-white',
        'disetujui' => 'bg-marian-300 text-marian-950',
        'menunggu' => 'bg-gold text-marian-950',
    ];
    $kolom = '[grid-template-columns:8.5rem_repeat(14,4.25rem)] md:[grid-template-columns:12rem_repeat(14,minmax(4.5rem,1fr))]';
    $hariIni = today();
@endphp

{{-- Tab --}}
<div class="inline-flex flex-wrap gap-1 rounded-3xl bg-white p-1.5 shadow-lembut ring-1 ring-marian-100">
    @foreach (\App\Http\Requests\JadwalRequest::TAB as $kunci => $label)
        <a href="{{ $tautan(['tab' => $kunci]) }}"
           class="rounded-2xl px-4 py-2 text-sm font-bold transition {{ $tab === $kunci ? 'bg-marian-800 text-white' : 'text-marian-800 hover:bg-marian-50' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- Filter --}}
<form method="GET" action="{{ route($rute) }}" class="mt-4 grid gap-3 rounded-3xl bg-white p-4 shadow-lembut ring-1 ring-marian-100 [grid-template-columns:repeat(auto-fit,minmax(min(100%,11rem),1fr))]">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="text-sm font-semibold text-marian-900">Mulai tanggal
        <x-text-input type="date" name="mulai" class="mt-1 block w-full" :value="$mulai->toDateString()" />
    </label>
    <label class="text-sm font-semibold text-marian-900">Kategori
        <x-select name="kategori_id" class="mt-1 block w-full">
            <option value="">Semua kategori</option>
            @foreach ($kategori as $k)
                <option value="{{ $k->id }}" @selected(request('kategori_id') === $k->id)>{{ $k->nama }}</option>
            @endforeach
        </x-select>
    </label>
    <label class="text-sm font-semibold text-marian-900">Lokasi
        <x-select name="lokasi_id" class="mt-1 block w-full">
            <option value="">Semua lokasi</option>
            @foreach ($lokasi as $l)
                <option value="{{ $l->id }}" @selected(request('lokasi_id') === $l->id)>{{ $l->nama }}</option>
            @endforeach
        </x-select>
    </label>
    <label class="text-sm font-semibold text-marian-900 [grid-column:span_2/span_2] max-sm:col-span-1">Cari
        <x-text-input name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Kode unit, nama, atau kode alat…" />
    </label>
    <div class="flex items-end gap-2">
        <x-primary-button class="flex-1">Tampilkan</x-primary-button>
    </div>
</form>

<x-input-error :messages="$errors->all()" class="mt-2" />

{{-- Navigasi periode + legenda --}}
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ $tautan(['mulai' => $mulai->copy()->subDays(14)->toDateString()]) }}" class="rounded-full bg-white px-3 py-1.5 font-semibold text-marian-800 ring-1 ring-marian-200 hover:bg-marian-50">← 14 hari</a>
        <a href="{{ $tautan(['mulai' => null]) }}" class="rounded-full bg-white px-3 py-1.5 font-semibold text-marian-800 ring-1 ring-marian-200 hover:bg-marian-50">Hari ini</a>
        <a href="{{ $tautan(['mulai' => $mulai->copy()->addDays(14)->toDateString()]) }}" class="rounded-full bg-white px-3 py-1.5 font-semibold text-marian-800 ring-1 ring-marian-200 hover:bg-marian-50">14 hari →</a>
        <span class="ms-1 text-marian-600">{{ $data['tanggal']->first()->translatedFormat('d M') }} – {{ $data['tanggal']->last()->translatedFormat('d M Y') }}</span>
    </div>
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-marian-700">
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-marian-200 bg-white"></span> Tersedia</li>
        @unless ($publik)
            <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-gold"></span> Menunggu verifikasi</li>
        @endunless
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-marian-300"></span> {{ $publik ? 'Dipesan' : 'Disetujui (belum diambil)' }}</li>
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-marian-800"></span> Dipinjam</li>
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-600"></span> Terlambat</li>
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-marian-950/15"></span> Tidak tersedia</li>
    </ul>
</div>

@if ($tab === 'tanggal')
    {{-- ================= PER TANGGAL: baris = unit, kolom = hari ================= --}}
    <p class="mt-2 text-right text-xs text-marian-500">{{ $data['jumlahUnit'] }} unit @if ($publik) · klik kotak kosong untuk mengajukan pada tanggal itu @endif</p>
    <div class="mt-2 overflow-x-auto rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <div class="min-w-max md:min-w-0">
            {{-- Kepala tanggal --}}
            <div class="grid {{ $kolom }} border-b border-marian-100 text-xs">
                <div class="sticky left-0 z-20 bg-white px-3 py-3 font-bold text-marian-700">Unit</div>
                @foreach ($data['tanggal'] as $t)
                    <div class="border-l border-marian-50 px-1 py-2 text-center {{ $t->isWeekend() ? 'bg-marian-50/70' : '' }} {{ $t->isSameDay($hariIni) ? 'bg-marian-100 font-bold text-marian-900' : 'text-marian-600' }}">
                        <div class="uppercase">{{ $t->translatedFormat('D') }}</div>
                        <div class="font-semibold">{{ $t->translatedFormat('d M') }}</div>
                    </div>
                @endforeach
            </div>

            @forelse ($data['perUnit'] as $baris)
                @php $u = $baris['unit']; @endphp
                <div class="grid {{ $kolom }} border-b border-marian-50 last:border-b-0">
                    <div class="sticky left-0 z-20 row-start-1 bg-white px-3 py-2" style="grid-column: 1;">
                        <div class="font-mono text-sm font-bold text-marian-900">{{ $u->kode_unit }}</div>
                        <div class="truncate text-[11px] text-marian-500">{{ $u->barang->nama }} · {{ $u->lokasi->nama }}</div>
                    </div>

                    {{-- Sel latar per hari (kosong = bisa diajukan di versi publik) --}}
                    @foreach ($data['tanggal'] as $i => $t)
                        @php
                            $terisi = collect($baris['blok'])->contains(fn ($b) => $i >= $b['kolomAwal'] && $i <= $b['kolomAkhir']);
                            $bisaAjukan = $publik && ! $baris['tidakTersedia'] && ! $terisi && $t->gte($hariIni);
                        @endphp
                        @if ($bisaAjukan)
                            <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $u->barang_id, 'tanggal_pinjam' => $t->toDateString()]) }}"
                               class="row-start-1 block min-h-[3.25rem] border-l border-marian-50 transition hover:bg-marian-50"
                               style="grid-column: {{ $i + 2 }};" title="Ajukan {{ $u->barang->nama }} mulai {{ $t->translatedFormat('d M Y') }}"></a>
                        @else
                            <div class="row-start-1 min-h-[3.25rem] border-l border-marian-50 {{ $baris['tidakTersedia'] ? 'bg-marian-950/10' : ($t->isWeekend() ? 'bg-marian-50/40' : '') }}"
                                 style="grid-column: {{ $i + 2 }};"></div>
                        @endif
                    @endforeach

                    @if ($baris['tidakTersedia'])
                        <div class="row-start-1 z-10 flex items-center px-3 text-xs font-semibold text-marian-600" style="grid-column: 2 / span 14;">
                            Tidak tersedia ({{ $u->status->label() }})
                        </div>
                    @endif

                    {{-- Balok pemakaian --}}
                    @foreach ($baris['blok'] as $b)
                        @php $kelas = 'row-start-1 z-10 mx-0.5 my-1.5 min-w-0 overflow-hidden rounded-xl px-2 py-1 text-left shadow-sm '.$warna[$b['jenis']]; @endphp
                        @if ($b['url'])
                            <a href="{{ $b['url'] }}" class="{{ $kelas }} hover:opacity-90" style="grid-column: {{ $b['kolomAwal'] + 2 }} / {{ $b['kolomAkhir'] + 3 }};" title="{{ $b['judul'] }} — {{ $b['sub'] }}">
                                <span class="block truncate text-[11px] font-bold">{{ $b['judul'] }}</span>
                                <span class="block truncate text-[10px] opacity-80">{{ $b['sub'] }}</span>
                            </a>
                        @else
                            <div class="{{ $kelas }}" style="grid-column: {{ $b['kolomAwal'] + 2 }} / {{ $b['kolomAkhir'] + 3 }};" title="{{ $b['judul'] }} — {{ $b['sub'] }}">
                                <span class="block truncate text-[11px] font-bold">{{ $b['judul'] }}</span>
                                <span class="block truncate text-[10px] opacity-80">{{ $b['sub'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <p class="p-8 text-center text-sm text-marian-500">Tidak ada unit yang cocok dengan filter.</p>
            @endforelse
        </div>
    </div>
@elseif ($tab === 'alat')
    {{-- ================= PER ALAT: terpakai / kapasitas per hari ================= --}}
    <div class="mt-4 overflow-x-auto rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
        <div class="min-w-max md:min-w-0">
            <div class="grid {{ $kolom }} border-b border-marian-100 text-xs">
                <div class="sticky left-0 z-20 bg-white px-3 py-3 font-bold text-marian-700">Alat</div>
                @foreach ($data['tanggal'] as $t)
                    <div class="border-l border-marian-50 px-1 py-2 text-center {{ $t->isSameDay($hariIni) ? 'bg-marian-100 font-bold text-marian-900' : 'text-marian-600' }}">
                        <div class="uppercase">{{ $t->translatedFormat('D') }}</div>
                        <div class="font-semibold">{{ $t->translatedFormat('d M') }}</div>
                    </div>
                @endforeach
            </div>
            @forelse ($data['perAlat'] as $baris)
                <div class="grid {{ $kolom }} border-b border-marian-50 last:border-b-0">
                    <div class="sticky left-0 z-20 bg-white px-3 py-2">
                        <div class="truncate text-sm font-bold text-marian-900">{{ $baris['barang']->nama }}</div>
                        <div class="font-mono text-[11px] text-marian-500">{{ $baris['barang']->kode }}</div>
                    </div>
                    @foreach ($baris['hari'] as $i => $h)
                        @php
                            $t = $data['tanggal'][$i];
                            $rasio = $h['kapasitas'] > 0 ? $h['terpakai'] / $h['kapasitas'] : null;
                            // Makin pekat = makin penuh
                            $sel = match (true) {
                                $rasio === null => 'bg-marian-950/10 text-marian-500',
                                $rasio >= 1 => 'bg-marian-800 text-white',
                                $rasio >= 0.5 => 'bg-marian-300 text-marian-950',
                                $rasio > 0 => 'bg-marian-100 text-marian-900',
                                default => 'bg-white text-marian-600',
                            };
                            $bisaAjukan = $publik && $rasio !== null && $rasio < 1 && $t->gte($hariIni);
                        @endphp
                        @if ($bisaAjukan)
                            <a href="{{ route('peminjam.ajuan.create', ['barang_id' => $baris['barang']->id, 'tanggal_pinjam' => $t->toDateString()]) }}"
                               class="m-1 flex items-center justify-center rounded-xl text-xs font-bold ring-1 ring-marian-100 transition hover:ring-magenta {{ $sel }}"
                               title="Ajukan {{ $baris['barang']->nama }} mulai {{ $t->translatedFormat('d M Y') }}">{{ $h['kapasitas'] - $h['terpakai'] }} sisa</a>
                        @else
                            <div class="m-1 flex items-center justify-center rounded-xl text-xs font-bold ring-1 ring-marian-100 {{ $sel }}">
                                {{ $h['kapasitas'] > 0 ? $h['terpakai'].'/'.$h['kapasitas'] : '—' }}
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <p class="p-8 text-center text-sm text-marian-500">Tidak ada alat yang cocok dengan filter.</p>
            @endforelse
        </div>
    </div>
    <p class="mt-2 text-xs text-marian-500">{{ $publik ? 'Angka = sisa unit pada tanggal itu. Klik untuk mengajukan.' : 'Angka = unit terpakai / unit yang bisa dipinjam (ajuan menunggu tidak dihitung).' }}</p>
@else
    {{-- ================= RINGKASAN MINGGUAN ================= --}}
    @php $r = $data['ringkasan']; @endphp
    <p class="mt-4 text-sm font-semibold text-marian-700">Minggu {{ $r['periode'] }}</p>
    <div class="mt-3 grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(min(100%,12rem),1fr))]">
        @foreach ([
            ['Tingkat pemakaian', $r['pemakaian'].'%', 'rata-rata unit terpakai dari kapasitas'],
            ['Ajuan masuk', $r['ajuan'], 'ajuan dibuat minggu ini'],
            ['Unit diserahkan', $r['diserahkan'], 'unit diambil peminjam'],
            ['Kembali terlambat', $r['kembaliTerlambat'], 'unit dikembalikan lewat jatuh tempo'],
            ['Sedang terlambat', $r['sedangTerlambat'], 'peminjaman belum kembali (hari ini)'],
        ] as [$label, $nilai, $ket])
            <div class="rounded-3xl bg-white p-5 shadow-lembut ring-1 ring-marian-100">
                <div class="text-sm text-marian-600">{{ $label }}</div>
                <div class="mt-1 text-3xl font-extrabold text-marian-900">{{ $nilai }}</div>
                <div class="mt-1 text-xs text-marian-500">{{ $ket }}</div>
            </div>
        @endforeach
    </div>
    <div class="mt-4 rounded-3xl bg-white p-5 shadow-lembut ring-1 ring-marian-100">
        <h3 class="font-bold text-marian-900">Alat paling sering diambil minggu ini</h3>
        <ol class="mt-3 space-y-2 text-sm">
            @forelse ($r['populer'] as $nama => $jumlah)
                <li class="flex items-center justify-between gap-3">
                    <span><span class="me-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-magenta text-xs font-bold text-white">{{ $loop->iteration }}</span>{{ $nama }}</span>
                    <span class="font-semibold text-marian-700">{{ $jumlah }} unit</span>
                </li>
            @empty
                <li class="text-marian-500">Belum ada alat yang diambil pada minggu ini.</li>
            @endforelse
        </ol>
    </div>
@endif
