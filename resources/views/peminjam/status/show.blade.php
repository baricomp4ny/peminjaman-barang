@php $p = $peminjaman; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl font-semibold leading-tight text-gray-800">Status Ajuan {{ $p->kode_peminjaman }}</h1>
            <button type="button" onclick="window.print()" class="no-print text-sm font-medium text-indigo-600 hover:text-indigo-800">Cetak bukti</button>
        </div>
    </x-slot>

    <style>
        @media print {
            nav, footer, .no-print { display: none !important; }
            body, main { background: #fff !important; }
        }
    </style>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @if ($p->status === 'disetujui')
                <div class="rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800">
                    Ajuan <strong>disetujui</strong>. Ambil barang ke petugas mulai <strong>{{ $p->tanggal_pinjam->translatedFormat('d F Y') }}</strong>
                    paling lambat <strong>{{ $batasAmbil->translatedFormat('d F Y') }}</strong>. Tunjukkan QR di samping dan bawa kartu identitas asli.
                    Lewat dari batas itu ajuan otomatis kedaluwarsa.
                </div>
            @elseif ($p->status === 'menunggu')
                <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Ajuan sedang <strong>menunggu verifikasi</strong> admin. Kabar persetujuan dikirim ke {{ app(\App\Services\IdentitasPeminjamService::class)->samarkan($p->peminjam->email) }}.
                </div>
            @endif

            @if ($perkiraanDenda > 0)
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Pinjaman ini terlambat {{ $p->hariTerlambatBerjalan() }} hari. Perkiraan denda sampai hari ini:
                    <strong>Rp{{ number_format($perkiraanDenda, 0, ',', '.') }}</strong>. Segera kembalikan barang.
                </div>
            @endif

            <x-detail-peminjaman :peminjaman="$p">
                @if ($p->bisaDibatalkan())
                    <form method="POST" action="{{ route('peminjam.status.batal', $p->kode_peminjaman) }}" class="no-print mt-6 border-t pt-4"
                          onsubmit="return confirm('Batalkan ajuan {{ $p->kode_peminjaman }}?');">
                        @csrf
                        <x-danger-button>Batalkan Ajuan</x-danger-button>
                        <p class="mt-1 text-xs text-gray-500">Ajuan bisa dibatalkan selama barang belum diambil.</p>
                    </form>
                @endif
            </x-detail-peminjaman>
        </div>

        {{-- QR kode peminjaman: dipindai petugas saat serah-terima --}}
        <div class="rounded-lg bg-white p-6 text-center shadow">
            <div class="text-sm font-semibold text-gray-800">QR Pengambilan</div>
            <div class="mx-auto mt-3 w-48 [&>svg]:h-auto [&>svg]:w-full">{!! $qr !!}</div>
            <div class="mt-2 font-mono text-lg font-bold tracking-wide">{{ $p->kode_peminjaman }}</div>
            <div class="text-sm text-gray-600">{{ $p->peminjam->name }}</div>
            <p class="mt-3 text-xs text-gray-500">Simpan kode ini atau screenshot QR untuk ditunjukkan ke petugas.</p>
        </div>
    </div>
</x-app-layout>
