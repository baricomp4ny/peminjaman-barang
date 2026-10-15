@props(['value'])

@php
    // Warna badge untuk status/kondisi yang dipakai di seluruh aplikasi
    $warna = [
        'tersedia' => 'bg-green-100 text-green-800',
        'baik' => 'bg-green-100 text-green-800',
        'aktif' => 'bg-green-100 text-green-800',
        'selesai' => 'bg-green-100 text-green-800',
        'dipinjam' => 'bg-blue-100 text-blue-800',
        'disetujui' => 'bg-indigo-100 text-indigo-800',
        'menunggu' => 'bg-amber-100 text-amber-800',
        'perbaikan' => 'bg-amber-100 text-amber-800',
        'rusak' => 'bg-amber-100 text-amber-800',
        'hilang' => 'bg-red-100 text-red-800',
        'ditolak' => 'bg-red-100 text-red-800',
        'nonaktif' => 'bg-gray-200 text-gray-700',
        'dibatalkan' => 'bg-gray-200 text-gray-700',
        'kedaluwarsa' => 'bg-gray-200 text-gray-700',
    ][$value] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize $warna"]) }}>{{ $value }}</span>
