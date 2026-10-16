@props(['variant' => 'primary'])

@php
    $kelas = $variant === 'primary'
        ? 'border-marian-800 bg-marian-800 text-white hover:bg-marian-900'
        : 'border-marian-950/20 bg-white text-marian-900 hover:border-marian-800';
@endphp

<a {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 border px-4 py-2 text-xs font-semibold transition $kelas"]) }}>
    {{ $slot }}
</a>
