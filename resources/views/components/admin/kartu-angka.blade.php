@props(['label', 'nilai', 'perhatian' => false, 'urutan' => 0])

@php
    // Gradasi biru per urutan kartu; batang mini hanya hiasan (pola tetap per urutan, bukan data)
    $latar = ['bg-marian-800', 'bg-marian-700', 'bg-marian-600', 'bg-marian-500'][$urutan % 4];
    $pola = [[8, 5, 7, 3, 6], [6, 8, 8, 7, 8], [2, 4, 2, 3, 4], [2, 2, 3, 2, 2]][$urutan % 4];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-2xl p-4 text-white shadow-lembut sm:p-5 $latar"]) }}>
    <div class="flex items-end justify-between gap-2">
        <span class="font-extrabold leading-none [font-size:clamp(1.75rem,3vw,2.25rem)]">{{ $nilai }}</span>
        <span class="flex items-end gap-1 opacity-50" aria-hidden="true">
            @foreach ($pola as $tinggi)
                <i class="w-1.5 rounded-sm bg-white sm:w-2" style="height: {{ $tinggi * 4 }}px"></i>
            @endforeach
        </span>
    </div>
    <div class="mt-2 text-[11px] font-semibold uppercase tracking-wider text-marian-100">{{ $label }}</div>
    {{-- Garis tepi kanan: magenta untuk angka yang perlu perhatian (rusak, hilang, terlambat) --}}
    <i class="absolute inset-y-0 right-0 w-1.5 {{ $perhatian && $nilai ? 'bg-magenta' : 'bg-white/25' }}" aria-hidden="true"></i>
</div>
