@use('App\Enums\StatusPeminjaman')
@php $p = $peminjaman; @endphp

<x-app-layout>
    <style>
        @media print {
            header, footer, .no-print { display: none !important; }
            body, main { background: #fff !important; }
        }
    </style>

    {{-- Kepala status --}}
    <section class="panel flex flex-wrap items-center justify-between gap-4 border-l-4 border-l-marian-800 px-6 py-6 sm:px-10">
        <div>
            <div class="font-mono text-sm font-semibold text-marian-800">{{ $p->kode_peminjaman }}</div>
            <h1 class="mt-1 font-semibold tracking-tight [font-size:clamp(1.4rem,3vw,2rem)]">Status Ajuan: <span class="capitalize">{{ $p->status->label() }}</span></h1>
            <p class="mt-1 text-sm text-marian-950/65">{{ $p->peminjam->name }} · <span class="font-mono">{{ $p->tanggal_pinjam->translatedFormat('d M Y') }} → {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}</span></p>
        </div>
        <x-secondary-button onclick="window.print()" class="no-print">Cetak bukti</x-secondary-button>
    </section>

    <div class="mt-6 grid items-start gap-6 lg:[grid-template-columns:minmax(0,1fr)_20rem]">
        <div class="space-y-4">
            @if ($p->status === StatusPeminjaman::Disetujui)
                <div class="panel border-l-4 border-l-marian-800 p-5 text-sm">
                    Ajuan <strong>disetujui</strong>. Ambil alat ke petugas mulai <strong>{{ $p->tanggal_pinjam->translatedFormat('d F Y') }}</strong>
                    paling lambat <strong>{{ $batasAmbil->translatedFormat('d F Y') }}</strong>. Tunjukkan QR dan bawa kartu identitas asli.
                    Lewat dari batas itu ajuan otomatis kedaluwarsa.
                </div>
            @elseif ($p->status === StatusPeminjaman::Menunggu)
                <div class="panel border-l-4 border-l-marian-300 p-5 text-sm text-marian-950/80">
                    Ajuan sedang <strong>menunggu verifikasi</strong> petugas. Kabar persetujuan dikirim ke {{ $emailSamar }}.
                </div>
            @endif

            @if ($perkiraanDenda > 0)
                <div class="border border-l-4 border-red-300 border-l-red-600 bg-red-50 p-5 text-sm text-red-800">
                    Pinjaman ini terlambat {{ $p->hariTerlambatBerjalan() }} hari. Perkiraan denda sampai hari ini:
                    <strong>{{ rupiah($perkiraanDenda) }}</strong>. Segera kembalikan alat.
                </div>
            @endif

            <x-detail-peminjaman :peminjaman="$p">
                @if ($p->bisaDibatalkan())
                    <form method="POST" action="{{ route('peminjam.status.batal', $p->kode_peminjaman) }}" class="no-print mt-6 border-t border-marian-950/15 pt-4"
                          onsubmit="return confirm('Batalkan ajuan {{ $p->kode_peminjaman }}?');">
                        @csrf
                        <x-danger-button>Batalkan Ajuan</x-danger-button>
                        <p class="mt-1 text-xs text-marian-950/55">Ajuan bisa dibatalkan selama alat belum diambil.</p>
                    </form>
                @endif
            </x-detail-peminjaman>
        </div>

        {{-- QR kode peminjaman: dipindai petugas saat serah-terima --}}
        <aside class="panel text-center lg:sticky lg:top-24">
            {{-- Gaya tiket/label lab: kepala bergaris, QR, sobekan putus-putus --}}
            <div class="flex items-center justify-between border-b border-marian-950/15 px-4 py-2 font-mono text-[11px] uppercase tracking-wider">
                <span class="text-marian-800">QR Pengambilan</span><span class="text-marian-950/45">{{ config('peminjaman.mitra_singkat') }}</span>
            </div>
            <div class="mx-auto my-5 w-52 border border-marian-950/15 p-3 [&>svg]:h-auto [&>svg]:w-full">{!! $qr !!}</div>
            <div class="border-t border-dashed border-marian-950/25 px-4 py-4">
            <div class="font-mono text-lg font-semibold tracking-wide text-marian-900">{{ $p->kode_peminjaman }}</div>
            <div class="text-sm text-marian-950/65">{{ $p->peminjam->name }}</div>
            <p class="mt-3 text-xs text-marian-950/55">Simpan kode ini atau screenshot QR untuk ditunjukkan ke petugas.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
