<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.partials.head', ['title' => 'Cetak Label QR'])
    <style>
        /* Saat dicetak: hanya label yang tampil, ukuran kertas A4 */
        @page { size: A4; margin: 10mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .label { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="no-print border-b bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-end gap-3 px-4 py-4">
            <a href="{{ route('admin.unit.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Unit Barang</a>
            <h1 class="text-lg font-semibold text-gray-800">Cetak Label QR</h1>

            <form method="GET" class="ms-auto flex flex-wrap items-end gap-2">
                @foreach (request('unit', []) as $id)
                    <input type="hidden" name="unit[]" value="{{ $id }}">
                @endforeach
                @unless (request()->filled('unit'))
                    <x-select name="barang_id" class="text-sm">
                        <option value="">Semua barang</option>
                        @foreach ($barang as $b)
                            <option value="{{ $b->id }}" @selected(request('barang_id') == $b->id)>{{ $b->nama }}</option>
                        @endforeach
                    </x-select>
                    <x-select name="lokasi_id" class="text-sm">
                        <option value="">Semua lokasi</option>
                        @foreach ($lokasi as $l)
                            <option value="{{ $l->id }}" @selected(request('lokasi_id') == $l->id)>{{ $l->nama }}</option>
                        @endforeach
                    </x-select>
                @endunless
                <x-select name="kolom" class="text-sm" aria-label="Label per baris">
                    @foreach ([3 => '3 per baris (besar)', 4 => '4 per baris', 5 => '5 per baris (kecil)'] as $n => $label)
                        <option value="{{ $n }}" @selected($kolom === $n)>{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-secondary-button type="submit">Terapkan</x-secondary-button>
                <x-primary-button type="button" onclick="window.print()">Cetak ({{ $unit->count() }})</x-primary-button>
            </form>
        </div>
        @if ($unit->isEmpty())
            <p class="mx-auto max-w-5xl px-4 pb-4 text-sm text-red-600">Tidak ada unit yang cocok dengan filter.</p>
        @endif
    </div>

    <main class="mx-auto max-w-5xl bg-white p-4 print:p-0">
        <div class="grid gap-2" style="grid-template-columns: repeat({{ $kolom }}, minmax(0, 1fr));">
            @foreach ($unit as $u)
                <div class="label flex flex-col items-center rounded border border-dashed border-gray-400 p-2 text-center">
                    <div class="w-full [&>svg]:h-auto [&>svg]:w-full">{!! $svg[$u->id] !!}</div>
                    <div class="mt-1 font-mono text-sm font-bold tracking-wide">{{ $u->kode_unit }}</div>
                    <div class="text-xs leading-tight">{{ $u->barang->nama }}</div>
                    <div class="text-[9px] leading-tight text-gray-500">{{ config('peminjaman.mitra') }}</div>
                </div>
            @endforeach
        </div>
    </main>
</body>
</html>
