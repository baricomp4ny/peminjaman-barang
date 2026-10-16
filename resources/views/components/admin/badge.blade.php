@props(['value'])

@php
    // Terima Enum (status/kondisi) maupun string
    $nilai = $value instanceof \BackedEnum ? $value->value : (string) $value;

    // Warna badge: gradasi biru lab; merah hanya untuk kondisi negatif (ditolak/hilang)
    $warna = match ($nilai) {
        'tersedia', 'baik', 'aktif', 'selesai' => 'bg-marian-100 text-marian-800 ring-1 ring-marian-200',
        'dipinjam' => 'bg-marian-800 text-white',
        'disetujui' => 'bg-marian-600 text-white',
        'menunggu' => 'bg-white text-marian-700 ring-1 ring-marian-300',
        'perbaikan', 'rusak' => 'bg-marian-200 text-marian-900',
        'hilang', 'ditolak' => 'bg-red-100 text-red-800',
        default => 'bg-marian-50 text-marian-500 ring-1 ring-marian-100',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize $warna"]) }}>{{ $nilai }}</span>
