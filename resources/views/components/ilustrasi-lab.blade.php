{{-- Gambar teknik garis tipis: botol kultur berisi planlet, erlenmeyer, cawan petri. Warna mengikuti currentColor. --}}
<svg {{ $attributes->merge(['class' => 'h-auto w-full text-marian-800']) }} viewBox="0 0 420 340" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Gambar teknik botol kultur jaringan berisi planlet">
    {{-- garis meja + skala --}}
    <path d="M24 296h372" />
    <path d="M24 306h372" stroke-opacity=".35" stroke-dasharray="2 6" />

    {{-- botol kultur besar --}}
    <g>
        <rect x="172" y="70" width="66" height="22" />
        <path d="M152 100h106v176a14 14 0 0 1-14 14h-78a14 14 0 0 1-14-14z" />
        <path d="M152 222h106" />
        <path d="M156 236h98M156 250h98M156 264h98" stroke-opacity=".3" />
        {{-- planlet --}}
        <path d="M205 222v-70" />
        <path d="M205 190c-22-4-30-20-26-34 16 2 26 14 26 34z" />
        <path d="M205 172c18-6 24-22 20-34-14 4-22 16-20 34z" />
        <path d="M205 156c-12-8-14-20-10-28 10 4 13 14 10 28z" />
        <path d="M205 222c-6 10-14 14-22 16M205 222c6 10 14 14 24 15M205 222v18" />
    </g>

    {{-- garis ukur tinggi botol --}}
    <g stroke-opacity=".55">
        <path d="M128 92v198M124 92h8M124 290h8" />
        <text x="118" y="196" fill="currentColor" stroke="none" font-family="IBM Plex Mono, monospace" font-size="10" text-anchor="end" transform="rotate(-90 118 196)">120 mm</text>
    </g>

    {{-- botol kultur kecil --}}
    <g>
        <rect x="292" y="152" width="40" height="16" />
        <path d="M280 174h64v104a12 12 0 0 1-12 12h-40a12 12 0 0 1-12-12z" />
        <path d="M280 246h64" />
        <path d="M312 246v-38" />
        <path d="M312 226c-14-2-18-12-16-20 10 1 16 8 16 20z" />
        <path d="M312 216c12-4 15-13 12-20-9 2-13 10-12 20z" />
    </g>

    {{-- erlenmeyer --}}
    <g>
        <path d="M84 150h32M92 150v34l-30 92a10 10 0 0 0 9 14h58a10 10 0 0 0 9-14l-30-92v-34" />
        <path d="M70 250h60" />
        <circle cx="92" cy="268" r="3" /><circle cx="108" cy="262" r="2" />
    </g>

    {{-- label anotasi --}}
    <g font-family="IBM Plex Mono, monospace" font-size="10" fill="currentColor" stroke="none">
        <text x="262" y="60">A — botol kultur</text>
        <text x="40" y="132">B — erlenmeyer</text>
        <text x="300" y="138">C — subkultur</text>
    </g>
    <path d="M258 64l-30 14" stroke-opacity=".55" />
    <path d="M70 136l20 12" stroke-opacity=".55" />
    <path d="M318 142v8" stroke-opacity=".55" />
</svg>
