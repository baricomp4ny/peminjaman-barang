@use('App\Enums\StatusPeminjaman')
@php $p = $peminjaman; @endphp

<x-app-layout>
    <style>
        @media print {
            header, footer, .no-print { display: none !important; }
            body, main { background: #fff !important; }
        }
    </style>

    {{-- Banner status --}}
    <section class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 px-6 py-7 text-white sm:px-10">
        <div aria-hidden="true" class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="font-mono text-sm text-marian-200">{{ $p->kode_peminjaman }}</div>
                <h1 class="mt-1 font-extrabold [font-size:clamp(1.4rem,3vw,2.2rem)]">Status Ajuan: <span class="capitalize">{{ $p->status->label() }}</span></h1>
                <p class="mt-1 text-sm text-marian-100">{{ $p->peminjam->name }} · {{ $p->tanggal_pinjam->translatedFormat('d M Y') }} s/d {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}</p>
            </div>
            <button type="button" onclick="window.print()" class="no-print rounded-full bg-white px-5 py-2.5 text-sm font-bold text-marian-900 hover:bg-marian-100">Cetak bukti</button>
        </div>
    </section>

    @if ($p->isTerlambat())
        <div class="mt-6 rounded-4xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            Pinjaman ini terlambat <strong>{{ $p->hariTerlambatBerjalan() }} hari</strong>. Segera kembalikan alat.
            Setelah pengembalian terlambat, pengajuan baru ditangguhkan sementara.
        </div>
    @endif

    {{-- Satu panel: keterangan + rincian di kiri, QR pengambilan di kanan --}}
    <div class="mt-6 grid overflow-hidden rounded-5xl bg-white shadow-lembut ring-1 ring-marian-100 lg:[grid-template-columns:minmax(0,1fr)_20rem]">
        <div class="p-6 sm:p-8">
            @if ($p->status === StatusPeminjaman::Disetujui)
                <p class="border-l-4 border-marian-600 pl-4 text-sm text-marian-900">
                    Ajuan <strong>disetujui</strong>. Ambil alat ke petugas mulai <strong>{{ $p->tanggal_pinjam->translatedFormat('d F Y') }}</strong>
                    paling lambat <strong>{{ $batasAmbil->translatedFormat('d F Y') }}</strong>. Tunjukkan QR dan bawa kartu identitas asli.
                    Lewat dari batas itu ajuan otomatis kedaluwarsa.
                </p>
            @elseif ($p->status === StatusPeminjaman::Menunggu)
                <p class="border-l-4 border-marian-300 pl-4 text-sm text-marian-800">
                    Ajuan sedang <strong>menunggu verifikasi</strong> petugas. Kabar persetujuan dikirim ke {{ $emailSamar }}.
                </p>
            @endif

            <x-detail-peminjaman :peminjaman="$p" polos class="mt-6">
                @if ($p->bisaDibatalkan())
                    <form method="POST" action="{{ route('peminjam.status.batal', $p->kode_peminjaman) }}" class="no-print mt-6 border-t border-marian-100 pt-4"
                          onsubmit="return confirm('Batalkan ajuan {{ $p->kode_peminjaman }}?');">
                        @csrf
                        <x-danger-button>Batalkan Ajuan</x-danger-button>
                        <p class="mt-1 text-xs text-marian-500">Ajuan bisa dibatalkan selama alat belum diambil.</p>
                    </form>
                @endif
            </x-detail-peminjaman>
        </div>

        {{-- QR kode peminjaman: dipindai petugas saat serah-terima --}}
        <aside class="border-t border-marian-100 bg-marian-50/60 p-6 text-center lg:border-l lg:border-t-0">
            <div class="lg:sticky lg:top-28">
                <div class="text-sm font-bold uppercase tracking-wider text-marian-700">QR Pengambilan</div>
                <div class="mx-auto mt-4 w-52 rounded-3xl bg-white p-3 ring-1 ring-marian-100 [&>svg]:h-auto [&>svg]:w-full">{!! $qr !!}</div>
                <div class="mt-3 font-mono text-lg font-extrabold tracking-wide text-marian-900">{{ $p->kode_peminjaman }}</div>
                <div class="text-sm text-marian-600">{{ $p->peminjam->name }}</div>
                <p class="mt-3 text-xs text-marian-500">Simpan kode ini atau screenshot QR untuk ditunjukkan ke petugas.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
