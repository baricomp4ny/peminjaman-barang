@props(['variant' => 'primary'])

@php
    $kelas = $variant === 'primary'
        ? 'bg-gradient-to-r from-marian-900 to-marian-700 text-white hover:from-marian-950 hover:to-marian-800 border-transparent'
        : 'bg-white text-marian-800 hover:bg-marian-50 border-marian-200';
@endphp

<a {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-full border px-4 py-2 text-xs font-bold shadow-sm transition $kelas"]) }}>
    {{ $slot }}
</a>
