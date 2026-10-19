@php $p = $peminjaman; @endphp

<x-app-layout>
    {{-- Konfirmasi pembatalan dari tautan bertanda tangan di email --}}
    <div class="mx-auto max-w-xl rounded-5xl bg-white p-6 shadow-lembut ring-1 ring-marian-100 sm:p-10">
        <span class="rounded-full bg-marian-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-marian-700 ring-1 ring-marian-200">Pembatalan ajuan</span>
        <h1 class="mt-4 font-extrabold text-marian-900 [font-size:clamp(1.4rem,3vw,2rem)]">Batalkan {{ $p->kode_peminjaman }}?</h1>

        <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-marian-500">Peminjam</dt><dd class="font-semibold">{{ $p->peminjam->name }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-marian-500">Tanggal</dt><dd>{{ $p->tanggal_pinjam->translatedFormat('d M Y') }} – {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-marian-500">Barang</dt><dd class="text-right">{{ $p->ringkasanBarang() }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-marian-500">Status</dt><dd><x-admin.badge :value="$p->status" /></dd></div>
        </dl>

        @if ($p->bisaDibatalkan())
            <form method="POST" action="{{ $aksi }}" class="mt-6 flex flex-wrap items-center gap-3 border-t border-marian-100 pt-5">
                @csrf
                <x-danger-button>Ya, batalkan ajuan</x-danger-button>
                <a href="{{ route('peminjam.katalog.index') }}" class="text-sm font-semibold text-marian-600 hover:text-marian-900">Tidak jadi</a>
            </form>
        @else
            {{-- Hanya ajuan menunggu / disetujui yang belum diambil yang bisa dibatalkan --}}
            <p class="mt-6 rounded-2xl bg-marian-50 p-4 text-sm text-marian-800">
                Ajuan ini tidak bisa dibatalkan lagi karena statusnya <strong>{{ $p->status->label() }}</strong>.
                Pembatalan hanya untuk ajuan yang masih menunggu verifikasi atau sudah disetujui tetapi alatnya belum diambil.
            </p>
        @endif
    </div>
</x-app-layout>
