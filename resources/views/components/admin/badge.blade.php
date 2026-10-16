@props(['value'])

@php
    // Terima Enum (status/kondisi) maupun string
    $nilai = $value instanceof \BackedEnum ? $value->value : (string) $value;

    // Badge kotak bergaris ala label lab; biru = aktif/berjalan, merah hanya untuk ditolak/hilang
    $warna = match ($nilai) {
        'tersedia', 'baik', 'aktif', 'selesai' => 'border-marian-800/40 bg-white text-marian-800',
        'dipinjam' => 'border-marian-800 bg-marian-800 text-white',
        'disetujui' => 'border-marian-600 bg-marian-600 text-white',
        'menunggu' => 'border-dashed border-marian-800 bg-white text-marian-800',
        'perbaikan', 'rusak' => 'border-marian-950/30 bg-marian-50 text-marian-950',
        'hilang', 'ditolak' => 'border-red-300 bg-red-50 text-red-700',
        default => 'border-marian-950/15 bg-white text-marian-950/55',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex border px-2 py-0.5 font-mono text-[11px] font-medium uppercase tracking-wider $warna"]) }}>{{ $nilai }}</span>
