<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Kartu QR Saya</h1>
    </x-slot>

    <style>
        @media print {
            nav, header, .no-print { display: none !important; }
            body, main { background: #fff !important; padding: 0 !important; }
        }
    </style>

    <div class="flex flex-col items-center gap-4">
        {{-- Ukuran kartu mengikuti KTP: 85,6 × 54 mm --}}
        <div class="flex overflow-hidden rounded-xl border border-gray-300 bg-white shadow-lg" style="width: 85.6mm; height: 54mm;">
            <div class="flex flex-1 flex-col justify-between bg-indigo-700 p-3 text-white">
                <div>
                    <div class="text-[8px] uppercase tracking-widest opacity-80">Kartu Peminjam</div>
                    <div class="text-[9px] font-semibold leading-tight">{{ config('peminjaman.mitra') }}</div>
                </div>
                <div>
                    <div class="text-sm font-bold leading-tight">{{ $user->name }}</div>
                    <div class="font-mono text-[10px]">{{ $user->nomor_identitas ?? '—' }}</div>
                    <div class="text-[9px] opacity-90">{{ $user->unit ?? '' }}</div>
                </div>
            </div>
            <div class="flex items-center justify-center bg-white p-2" style="width: 30mm;">
                <div class="w-full [&>svg]:h-auto [&>svg]:w-full">{!! $svg !!}</div>
            </div>
        </div>

        <div class="no-print max-w-md text-center text-sm text-gray-600">
            Tunjukkan kartu ini (cetak atau dari layar HP) kepada petugas saat mengambil barang.
            Petugas akan memindai QR untuk membuka ajuan Anda yang sudah disetujui.
            Bawa juga kartu identitas asli untuk dicocokkan.
        </div>
        <x-primary-button class="no-print" type="button" onclick="window.print()">Cetak Kartu</x-primary-button>
    </div>
</x-app-layout>
