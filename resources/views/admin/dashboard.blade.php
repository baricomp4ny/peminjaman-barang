<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-marian-900">Dashboard</h1>
    </x-slot>

    {{-- Strip ringkasan: satu kartu dibagi sekat garis, tiap angka menuju halaman terkait --}}
    <div class="grid grid-cols-2 divide-x divide-y divide-marian-100 overflow-hidden rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100 sm:grid-cols-3 sm:divide-y-0 lg:grid-cols-5">
        @foreach ([
            ['Ajuan menunggu', $kartu['menunggu'], route('admin.ajuan.index'), 'text-marian-600'],
            ['Siap diambil', $kartu['siapAmbil'], route('admin.peminjaman.index', ['tab' => 'disetujui']), 'text-indigo-600'],
            ['Sedang dipinjam', $kartu['dipinjam'], route('admin.peminjaman.index', ['tab' => 'dipinjam']), 'text-marian-700'],
            ['Terlambat', $kartu['terlambat'], route('admin.peminjaman.index', ['tab' => 'terlambat']), 'text-red-600'],
            ['Kembali terlambat (bulan ini)', $kartu['kembaliTerlambatBulanIni'].' unit', route('admin.laporan.show', 'keterlambatan'), 'text-marian-900'],
        ] as [$label, $nilai, $url, $warna])
            <a href="{{ $url }}" class="p-4 transition hover:bg-marian-50">
                <div class="text-sm text-marian-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold {{ $warna }}">{{ $nilai }}</div>
            </a>
        @endforeach
    </div>

    {{-- Grafik pemakaian (Chart.js, resources/js/grafik-dashboard.js); tabel di <details> sebagai alternatif teks --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2" x-data="grafikDashboard(@js($grafik))">
        <section class="rounded-3xl bg-white p-4 shadow-lembut ring-1 ring-marian-100">
            <h2 class="font-semibold text-marian-900">Peminjaman per bulan</h2>
            <p class="text-xs text-marian-500">Jumlah transaksi diserahkan · {{ $grafik['periode'] }}</p>
            <div class="relative mt-3 h-56"><canvas x-ref="bulan" role="img" aria-label="Grafik batang jumlah peminjaman per bulan"></canvas></div>
            <details class="mt-2 text-xs text-marian-600">
                <summary class="cursor-pointer">Lihat tabel</summary>
                <table class="mt-2 w-full">
                    @foreach ($grafik['bulan']['label'] as $i => $label)
                        <tr class="border-t border-marian-50"><td class="py-1">{{ $label }}</td><td class="py-1 text-right font-semibold text-marian-900">{{ $grafik['bulan']['nilai'][$i] }}</td></tr>
                    @endforeach
                </table>
            </details>
        </section>

        <section class="rounded-3xl bg-white p-4 shadow-lembut ring-1 ring-marian-100">
            <h2 class="font-semibold text-marian-900">5 alat terpopuler</h2>
            <p class="text-xs text-marian-500">Jumlah unit dipinjam · {{ $grafik['periode'] }}</p>
            @if ($grafik['populer']['nilai'])
                <div class="relative mt-3 h-56"><canvas x-ref="populer" role="img" aria-label="Grafik batang lima alat paling sering dipinjam"></canvas></div>
                <details class="mt-2 text-xs text-marian-600">
                    <summary class="cursor-pointer">Lihat tabel</summary>
                    <table class="mt-2 w-full">
                        @foreach ($grafik['populer']['label'] as $i => $label)
                            <tr class="border-t border-marian-50"><td class="py-1">{{ $label }}</td><td class="py-1 text-right font-semibold text-marian-900">{{ $grafik['populer']['nilai'][$i] }} unit</td></tr>
                        @endforeach
                    </table>
                </details>
            @else
                <p class="mt-6 text-center text-sm text-marian-500">Belum ada alat yang dipinjam pada periode ini.</p>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="font-semibold text-marian-900">Ajuan menunggu verifikasi</h2>
                    <a href="{{ route('admin.ajuan.index') }}" class="text-sm text-indigo-600 hover:underline">Semua</a>
                </div>
                <div class="divide-y">
                    @forelse ($ajuanTerbaru as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-marian-50">
                            <span>
                                <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                                <span class="block text-marian-500">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap text-marian-500">{{ $p->tanggal_pinjam->translatedFormat('d M') }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-500">Tidak ada ajuan menunggu.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-marian-900">Jatuh tempo hari ini & besok</h2></div>
                <div class="divide-y">
                    @forelse ($jatuhTempo as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-marian-50">
                            <span>
                                <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                                <span class="block text-marian-500">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap {{ $p->tanggal_rencana_kembali->isToday() ? 'font-semibold text-marian-600' : 'text-marian-500' }}">
                                {{ $p->tanggal_rencana_kembali->isToday() ? 'Hari ini' : 'Besok' }}
                            </span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-500">Tidak ada yang jatuh tempo.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-4 shadow-lembut ring-1 ring-marian-100">
                <h2 class="font-semibold text-marian-900">Status unit ({{ $totalUnit }})</h2>
                <div class="mt-3 space-y-2">
                    @foreach (['tersedia' => 'bg-marian-400', 'dipinjam' => 'bg-marian-700', 'perbaikan' => 'bg-marian-500', 'hilang' => 'bg-red-500'] as $s => $warna)
                        @php $n = (int) ($statusUnit[$s] ?? 0); @endphp
                        <div>
                            <div class="flex justify-between text-sm"><span class="capitalize">{{ $s }}</span><span class="font-medium">{{ $n }}</span></div>
                            <div class="mt-1 h-2 rounded bg-marian-50">
                                <div class="h-2 rounded {{ $warna }}" style="width: {{ $totalUnit ? round($n / $totalUnit * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl bg-white shadow-lembut ring-1 ring-marian-100">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-red-700">Terlambat</h2></div>
                <div class="divide-y">
                    @forelse ($terlambat as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="block px-4 py-3 text-sm hover:bg-marian-50">
                            <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                            <span class="float-right font-semibold text-red-600">+{{ $p->hariTerlambatBerjalan() }} hari</span>
                            <span class="block text-marian-500">{{ $p->ringkasanBarang() }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-500">Tidak ada pinjaman terlambat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
