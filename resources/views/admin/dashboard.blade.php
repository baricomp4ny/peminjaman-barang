<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
    </x-slot>

    {{-- Kartu ringkasan, masing-masing menuju halaman terkait --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([
            ['Ajuan menunggu', $kartu['menunggu'], route('admin.ajuan.index'), 'text-amber-600'],
            ['Siap diambil', $kartu['siapAmbil'], route('admin.peminjaman.index', ['tab' => 'disetujui']), 'text-indigo-600'],
            ['Sedang dipinjam', $kartu['dipinjam'], route('admin.peminjaman.index', ['tab' => 'dipinjam']), 'text-blue-600'],
            ['Terlambat', $kartu['terlambat'], route('admin.peminjaman.index', ['tab' => 'terlambat']), 'text-red-600'],
            ['Denda bulan ini', 'Rp'.number_format($kartu['dendaBulanIni'], 0, ',', '.'), route('admin.laporan.show', 'denda'), 'text-gray-800'],
        ] as [$label, $nilai, $url, $warna])
            <a href="{{ $url }}" class="rounded-lg bg-white p-4 shadow hover:ring-2 hover:ring-indigo-200">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold {{ $warna }}">{{ $nilai }}</div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg bg-white shadow">
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="font-semibold text-gray-800">Ajuan menunggu verifikasi</h2>
                    <a href="{{ route('admin.ajuan.index') }}" class="text-sm text-indigo-600 hover:underline">Semua</a>
                </div>
                <div class="divide-y">
                    @forelse ($ajuanTerbaru as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-gray-50">
                            <span>
                                <span class="font-medium text-gray-800">{{ $p->peminjam->name }}</span>
                                <span class="block text-gray-500">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap text-gray-500">{{ $p->tanggal_pinjam->translatedFormat('d M') }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500">Tidak ada ajuan menunggu.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg bg-white shadow">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-gray-800">Jatuh tempo hari ini & besok</h2></div>
                <div class="divide-y">
                    @forelse ($jatuhTempo as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-gray-50">
                            <span>
                                <span class="font-medium text-gray-800">{{ $p->peminjam->name }}</span>
                                <span class="block text-gray-500">{{ $p->ringkasanBarang() }}</span>
                            </span>
                            <span class="whitespace-nowrap {{ $p->tanggal_rencana_kembali->isToday() ? 'font-semibold text-amber-600' : 'text-gray-500' }}">
                                {{ $p->tanggal_rencana_kembali->isToday() ? 'Hari ini' : 'Besok' }}
                            </span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500">Tidak ada yang jatuh tempo.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg bg-white p-4 shadow">
                <h2 class="font-semibold text-gray-800">Status unit ({{ $totalUnit }})</h2>
                <div class="mt-3 space-y-2">
                    @foreach (['tersedia' => 'bg-green-500', 'dipinjam' => 'bg-blue-500', 'perbaikan' => 'bg-amber-500', 'hilang' => 'bg-red-500'] as $s => $warna)
                        @php $n = (int) ($statusUnit[$s] ?? 0); @endphp
                        <div>
                            <div class="flex justify-between text-sm"><span class="capitalize">{{ $s }}</span><span class="font-medium">{{ $n }}</span></div>
                            <div class="mt-1 h-2 rounded bg-gray-100">
                                <div class="h-2 rounded {{ $warna }}" style="width: {{ $totalUnit ? round($n / $totalUnit * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg bg-white shadow">
                <div class="border-b px-4 py-3"><h2 class="font-semibold text-red-700">Terlambat</h2></div>
                <div class="divide-y">
                    @forelse ($terlambat as $p)
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="block px-4 py-3 text-sm hover:bg-gray-50">
                            <span class="font-medium text-gray-800">{{ $p->peminjam->name }}</span>
                            <span class="float-right font-semibold text-red-600">+{{ $p->hariTerlambatBerjalan() }} hari</span>
                            <span class="block text-gray-500">{{ $p->ringkasanBarang() }}</span>
                        </a>
                    @empty
                        <p class="p-6 text-center text-sm text-gray-500">Tidak ada pinjaman terlambat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
