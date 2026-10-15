<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Halo, {{ Auth::user()->name }}</h1>
    </x-slot>

    @if ($terlambat->isNotEmpty())
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            Anda punya {{ $terlambat->count() }} pinjaman yang <strong>terlambat</strong>. Segera kembalikan untuk menghindari denda bertambah.
            Selama masih terlambat, Anda tidak bisa mengajukan peminjaman baru.
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm text-gray-500">Pinjaman aktif</div>
            <div class="text-2xl font-semibold">{{ $aktif->count() }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm text-gray-500">Notifikasi belum dibaca</div>
            <div class="text-2xl font-semibold">{{ $jumlahNotif }}</div>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
            <div class="text-sm text-gray-500">Peminjaman selesai</div>
            <div class="text-2xl font-semibold">{{ $jumlahSelesai }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-white shadow lg:col-span-2">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h2 class="font-semibold text-gray-800">Pinjaman berjalan</h2>
                <x-link-button :href="route('peminjam.ajuan.create')" class="!px-3">+ Ajukan</x-link-button>
            </div>
            <div class="divide-y">
                @forelse ($aktif as $p)
                    <a href="{{ route('peminjam.pinjaman.show', $p) }}" class="block px-4 py-3 hover:bg-gray-50">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-gray-800">{{ $p->ringkasanBarang() }}</span>
                            <span class="flex items-center gap-2">
                                @if ($p->isTerlambat())
                                    <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Terlambat</span>
                                @endif
                                <x-admin.badge :value="$p->status" />
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $p->kode_peminjaman }} · {{ $p->tanggal_pinjam->translatedFormat('d M') }} s/d {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}
                        </div>
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-gray-500">Tidak ada pinjaman berjalan.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg bg-white shadow">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h2 class="font-semibold text-gray-800">Notifikasi terbaru</h2>
                <a href="{{ route('peminjam.notifikasi.index') }}" class="text-sm text-indigo-600 hover:underline">Semua</a>
            </div>
            <div class="divide-y">
                @forelse ($notifikasi as $n)
                    <a href="{{ route('peminjam.notifikasi.buka', $n->id) }}" class="block px-4 py-3 text-sm hover:bg-gray-50">
                        <span class="block font-medium text-gray-800">{{ $n->data['judul'] ?? '' }}</span>
                        <span class="block text-gray-600">{{ $n->data['pesan'] ?? '' }}</span>
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-gray-500">Tidak ada notifikasi baru.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
