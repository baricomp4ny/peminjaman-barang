<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('peminjam.pinjaman.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Pinjaman Saya</a>
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Detail Pinjaman</h1>
    </x-slot>

    <div class="max-w-3xl space-y-4">
        @if ($peminjaman->status === 'disetujui')
            <div class="rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
                Ajuan disetujui. Ambil barang ke petugas mulai <strong>{{ $peminjaman->tanggal_pinjam->translatedFormat('d F Y') }}</strong>
                paling lambat <strong>{{ $batasAmbil->translatedFormat('d F Y') }}</strong>. Lewat dari itu ajuan otomatis kedaluwarsa.
            </div>
        @elseif ($peminjaman->status === 'menunggu')
            <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Ajuan sedang menunggu verifikasi admin.</div>
        @endif

        @if ($perkiraanDenda > 0)
            <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                Pinjaman ini terlambat {{ $peminjaman->hariTerlambatBerjalan() }} hari. Perkiraan denda sampai hari ini:
                <strong>Rp{{ number_format($perkiraanDenda, 0, ',', '.') }}</strong>. Segera kembalikan barang.
            </div>
        @endif

        <x-detail-peminjaman :peminjaman="$peminjaman">
            @if ($peminjaman->bisaDibatalkan())
                <form method="POST" action="{{ route('peminjam.pinjaman.batal', $peminjaman) }}" class="mt-6 border-t pt-4"
                      onsubmit="return confirm('Batalkan ajuan {{ $peminjaman->kode_peminjaman }}?');">
                    @csrf
                    <x-danger-button>Batalkan Ajuan</x-danger-button>
                    <p class="mt-1 text-xs text-gray-500">Ajuan bisa dibatalkan selama barang belum diambil.</p>
                </form>
            @endif
        </x-detail-peminjaman>
    </div>
</x-app-layout>
