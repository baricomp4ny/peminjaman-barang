{{-- Ilustrasi kultur jaringan: botol kultur berisi planlet, cawan petri, dan erlenmeyer. Hanya gradasi biru. --}}
<svg {{ $attributes->merge(['class' => 'h-auto w-full']) }} viewBox="0 0 420 340" fill="none" role="img" aria-label="Ilustrasi botol kultur jaringan berisi planlet">
    <defs>
        <linearGradient id="kaca" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#FFFFFF" stop-opacity=".95" />
            <stop offset="1" stop-color="#DDE3F6" stop-opacity=".85" />
        </linearGradient>
        <linearGradient id="media" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#93A5E2" />
            <stop offset="1" stop-color="#4863C4" />
        </linearGradient>
        <linearGradient id="daun" x1="0" y1="1" x2="1" y2="0">
            <stop offset="0" stop-color="#1E2F72" />
            <stop offset="1" stop-color="#3450B0" />
        </linearGradient>
    </defs>

    {{-- lingkaran latar --}}
    <circle cx="230" cy="170" r="150" fill="#FFFFFF" fill-opacity=".08" />
    <circle cx="230" cy="170" r="108" fill="#FFFFFF" fill-opacity=".08" />

    {{-- meja --}}
    <rect x="30" y="292" width="380" height="14" rx="7" fill="#FFFFFF" fill-opacity=".25" />

    {{-- botol kultur besar --}}
    <g>
        <rect x="170" y="70" width="70" height="26" rx="6" fill="#BCC7EE" />
        <path d="M150 104c0-6 5-10 11-10h88c6 0 11 4 11 10v172c0 9-7 16-16 16h-78c-9 0-16-7-16-16z" fill="url(#kaca)" stroke="#FFFFFF" stroke-width="3" />
        <path d="M153 222h104v54c0 7-6 13-13 13h-78c-7 0-13-6-13-13z" fill="url(#media)" />
        {{-- planlet --}}
        <path d="M205 222v-70" stroke="#1E2F72" stroke-width="4" stroke-linecap="round" />
        <path d="M205 190c-22-4-30-20-26-34 16 2 26 14 26 34z" fill="url(#daun)" />
        <path d="M205 172c18-6 24-22 20-34-14 4-22 16-20 34z" fill="url(#daun)" />
        <path d="M205 156c-12-8-14-20-10-28 10 4 13 14 10 28z" fill="url(#daun)" />
        <path d="M205 222c-6 10-14 14-22 16M205 222c6 10 14 14 24 15M205 222v18" stroke="#EEF1FB" stroke-width="2.5" stroke-linecap="round" />
    </g>

    {{-- botol kultur kecil --}}
    <g>
        <rect x="290" y="150" width="44" height="18" rx="5" fill="#BCC7EE" />
        <path d="M278 176c0-4 3-7 7-7h54c4 0 7 3 7 7v104c0 6-5 12-12 12h-44c-7 0-12-6-12-12z" fill="url(#kaca)" stroke="#FFFFFF" stroke-width="3" />
        <path d="M281 246h62v34c0 5-4 9-9 9h-44c-5 0-9-4-9-9z" fill="url(#media)" />
        <path d="M312 246v-38" stroke="#1E2F72" stroke-width="3" stroke-linecap="round" />
        <path d="M312 226c-14-2-18-12-16-20 10 1 16 8 16 20z" fill="url(#daun)" />
        <path d="M312 216c12-4 15-13 12-20-9 2-13 10-12 20z" fill="url(#daun)" />
    </g>

    {{-- erlenmeyer --}}
    <g>
        <path d="M86 150h28M92 150v34l-30 86c-2 6 2 12 8 12h60c6 0 10-6 8-12l-30-86v-34" fill="url(#kaca)" stroke="#FFFFFF" stroke-width="3" stroke-linejoin="round" />
        <path d="M72 246h56l10 28c1 4-1 8-6 8H68c-5 0-7-4-6-8z" fill="url(#media)" />
        <circle cx="92" cy="262" r="4" fill="#EEF1FB" /><circle cx="108" cy="254" r="3" fill="#EEF1FB" />
    </g>

    {{-- cawan petri --}}
    <g>
        <ellipse cx="350" cy="296" rx="46" ry="10" fill="#FFFFFF" fill-opacity=".9" />
        <ellipse cx="350" cy="292" rx="46" ry="10" fill="#DDE3F6" stroke="#FFFFFF" stroke-width="2" />
        <circle cx="336" cy="291" r="4" fill="#4863C4" /><circle cx="356" cy="294" r="3" fill="#3450B0" /><circle cx="368" cy="289" r="3.5" fill="#6A82D4" />
    </g>

    {{-- kilau --}}
    <path d="M168 120v120" stroke="#FFFFFF" stroke-opacity=".7" stroke-width="5" stroke-linecap="round" />
    <path d="M60 60l6 14 14 6-14 6-6 14-6-14-14-6 14-6z" fill="#FFFFFF" fill-opacity=".8" />
    <path d="M370 80l4 9 9 4-9 4-4 9-4-9-9-4 9-4z" fill="#FFFFFF" fill-opacity=".6" />
</svg>
