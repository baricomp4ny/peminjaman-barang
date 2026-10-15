@props(['value'])

@php
    // Warna badge status/kondisi: gradasi biru lab; merah hanya untuk kondisi negatif (ditolak/hilang)
    $warna = [
        'tersedia' => 'bg-marian-100 text-marian-800 ring-1 ring-marian-200',
        'baik' => 'bg-marian-100 text-marian-800 ring-1 ring-marian-200',
        'aktif' => 'bg-marian-100 text-marian-800 ring-1 ring-marian-200',
        'selesai' => 'bg-marian-100 text-marian-800 ring-1 ring-marian-200',
        'dipinjam' => 'bg-marian-800 text-white',
        'disetujui' => 'bg-marian-600 text-white',
        'menunggu' => 'bg-white text-marian-700 ring-1 ring-marian-300',
        'perbaikan' => 'bg-marian-200 text-marian-900',
        'rusak' => 'bg-marian-200 text-marian-900',
        'hilang' => 'bg-red-100 text-red-800',
        'ditolak' => 'bg-red-100 text-red-800',
        'nonaktif' => 'bg-marian-50 text-marian-500 ring-1 ring-marian-100',
        'dibatalkan' => 'bg-marian-50 text-marian-500 ring-1 ring-marian-100',
        'kedaluwarsa' => 'bg-marian-50 text-marian-500 ring-1 ring-marian-100',
    ][$value] ?? 'bg-marian-50 text-marian-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize $warna"]) }}>{{ $value }}</span>
