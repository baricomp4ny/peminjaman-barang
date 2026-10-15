<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Pinjaman Saya</h1>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'dipinjam' => 'Dipinjam', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan', 'kedaluwarsa' => 'Kedaluwarsa'] as $nilai => $label)
            <a href="{{ route('peminjam.pinjaman.index', array_filter(['status' => $nilai])) }}"
               class="rounded-full px-3 py-1 {{ request('status', '') === $nilai ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($pinjaman as $p)
            <a href="{{ route('peminjam.pinjaman.show', $p) }}" class="block rounded-lg bg-white p-4 shadow hover:ring-2 hover:ring-indigo-200">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-mono text-sm text-gray-500">{{ $p->kode_peminjaman }}</span>
                    <span class="flex items-center gap-2">
                        @if ($p->status === 'dipinjam' && $p->tanggal_rencana_kembali->lt(today()))
                            <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">Terlambat</span>
                        @endif
                        <x-admin.badge :value="$p->status" />
                    </span>
                </div>
                <div class="mt-1 font-medium text-gray-800">{{ $p->ringkasanBarang() }}</div>
                <div class="text-sm text-gray-500">{{ $p->tanggal_pinjam->translatedFormat('d M Y') }} s/d {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}</div>
            </a>
        @empty
            <div class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                Belum ada pinjaman. <a href="{{ route('peminjam.katalog.index') }}" class="text-indigo-600 underline">Lihat katalog</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $pinjaman->links() }}</div>
</x-app-layout>
