<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-marian-900">Dashboard</h1>
    </x-slot>

    {{-- Kartu ringkasan, masing-masing menuju halaman terkait --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Ajuan menunggu', $kartu['menunggu'], route('admin.ajuan.index'), 'text-marian-950/65'],
            ['Siap diambil', $kartu['siapAmbil'], route('admin.peminjaman.index', ['tab' => 'disetujui']), 'text-indigo-600'],
            ['Sedang dipinjam', $kartu['dipinjam'], route('admin.peminjaman.index', ['tab' => 'dipinjam']), 'text-marian-700'],
            ['Terlambat', $kartu['terlambat'], route('admin.peminjaman.index', ['tab' => 'terlambat']), 'text-red-600'],
            ['Denda bulan ini', rupiah($kartu['dendaBulanIni']), route('admin.laporan.show', 'denda'), 'text-marian-900'],
        ] as [$label, $nilai, $url, $warna])
            {{-- Kartu ringkasan ala panel pembacaan alat ukur: label mono + angka mono --}}
            <a href="{{ $url }}" class="panel border-t-2 border-t-marian-800 p-4 transition hover:border-marian-800">
                <div class="font-mono text-[11px] uppercase tracking-wider text-marian-950/55">{{ $label }}</div>
                <div class="mt-2 font-mono text-2xl font-semibold {{ $warna }}">{{ $nilai }}</div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-3xl bg-white border border-marian-950/15">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="font-semibold text-marian-900">Ajuan menunggu verifikasi</h2>
                    <a href="{{ route('admin.ajuan.index') }}" class="text-sm text-indigo-600 hover:underline">Semua</a>
                </div>
                <div class="divide-y">
                    @forelse ($ajuanTerbaru as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-marian-50">
                            <span>
                                <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                                <span class="block text-marian-950/55">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap text-marian-950/55">{{ $p->tanggal_pinjam->translatedFormat('d M') }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-950/55">Tidak ada ajuan menunggu.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl bg-white border border-marian-950/15">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-marian-900">Jatuh tempo hari ini & besok</h2></div>
                <div class="divide-y">
                    @forelse ($jatuhTempo as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-marian-50">
                            <span>
                                <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                                <span class="block text-marian-950/55">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap {{ $p->tanggal_rencana_kembali->isToday() ? 'font-semibold text-marian-950/65' : 'text-marian-950/55' }}">
                                {{ $p->tanggal_rencana_kembali->isToday() ? 'Hari ini' : 'Besok' }}
                            </span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-950/55">Tidak ada yang jatuh tempo.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl bg-white p-4 border border-marian-950/15">
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

            <div class="rounded-3xl bg-white border border-marian-950/15">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-red-700">Terlambat</h2></div>
                <div class="divide-y">
                    @forelse ($terlambat as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="block px-4 py-3 text-sm hover:bg-marian-50">
                            <span class="font-medium text-marian-900">{{ $p->peminjam->name }}</span>
                            <span class="float-right font-semibold text-red-600">+{{ $p->hariTerlambatBerjalan() }} hari</span>
                            <span class="block text-marian-950/55">{{ $p->ringkasanBarang() }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-marian-950/55">Tidak ada pinjaman terlambat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
