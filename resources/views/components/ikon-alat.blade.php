@props(['nama' => ''])

@php
    // Pilih ikon berdasarkan kata kunci nama alat/kategori (garis, mengikuti currentColor)
    $n = \Illuminate\Support\Str::lower($nama);
    $jenis = match (true) {
        str_contains($n, 'mikroskop') => 'mikroskop',
        str_contains($n, 'ph') || str_contains($n, 'ukur') || str_contains($n, 'analisis') => 'meter',
        str_contains($n, 'timbangan') => 'timbangan',
        str_contains($n, 'pipet') => 'pipet',
        str_contains($n, 'bunsen') || str_contains($n, 'steril') => 'api',
        str_contains($n, 'pinset') || str_contains($n, 'scalpel') || str_contains($n, 'inokulasi') => 'pinset',
        str_contains($n, 'petri') || str_contains($n, 'cawan') => 'cawan',
        str_contains($n, 'erlenmeyer') || str_contains($n, 'gelas') || str_contains($n, 'wadah') || str_contains($n, 'media') => 'labu',
        str_contains($n, 'hot plate') || str_contains($n, 'stirrer') => 'pemanas',
        default => 'labu',
    };
@endphp

<svg {{ $attributes->merge(['class' => 'h-10 w-10']) }} viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($jenis)
        @case('mikroskop')
            <path d="M20 6h8l-2 14h-4z" /><path d="M24 20v6" /><path d="M18 26h14" /><path d="M30 14c6 2 8 8 6 14" />
            <path d="M14 40h22" /><path d="M24 34v6" /><circle cx="24" cy="31" r="3" />
            @break
        @case('meter')
            <rect x="12" y="6" width="18" height="26" rx="4" /><rect x="16" y="10" width="10" height="7" rx="1.5" />
            <path d="M21 32v6" /><path d="M21 38c0 3 6 3 6 0v-2" /><circle cx="18" cy="24" r="1.5" /><circle cx="24" cy="24" r="1.5" />
            @break
        @case('timbangan')
            <rect x="8" y="30" width="32" height="10" rx="3" /><path d="M14 30v-4h20v4" /><path d="M16 22h16" />
            <path d="M18 22l-2-8h16l-2 8" /><circle cx="33" cy="35" r="1.5" />
            @break
        @case('pipet')
            <path d="M30 6l6 6" /><path d="M27 9l6 6" /><path d="M30 12L14 28l-2 8 8-2 16-16" /><path d="M10 38l2-2" />
            @break
        @case('api')
            <path d="M24 6c4 6 8 9 8 15a8 8 0 0 1-16 0c0-4 2-6 4-8 0 3 1 5 3 6 0-5 0-9 1-13z" /><path d="M16 34h16l-2 8H18z" />
            @break
        @case('pinset')
            <path d="M14 6l10 30" /><path d="M22 6l2 30" /><path d="M24 36l1 6" /><path d="M32 8l-4 22" /><path d="M36 8c2 6 0 14-6 22" />
            @break
        @case('cawan')
            <ellipse cx="24" cy="22" rx="16" ry="6" /><path d="M8 22v6c0 3.3 7.2 6 16 6s16-2.7 16-6v-6" />
            <circle cx="18" cy="21" r="1.5" /><circle cx="26" cy="23" r="1.5" /><circle cx="30" cy="20" r="1" />
            @break
        @case('pemanas')
            <rect x="8" y="26" width="32" height="14" rx="3" /><path d="M14 26v-2h20v2" /><path d="M18 18c0-4 4-4 4-8" />
            <path d="M26 18c0-4 4-4 4-8" /><circle cx="16" cy="33" r="2" /><path d="M24 33h10" />
            @break
        @default
            <path d="M19 6h10" /><path d="M21 6v12L10 38a3 3 0 0 0 2.6 4h22.8a3 3 0 0 0 2.6-4L27 18V6" /><path d="M14 30h20" />
            <path d="M22 26c2-4 6-2 6-6" />
    @endswitch
</svg>
