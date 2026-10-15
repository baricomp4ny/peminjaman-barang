@props(['variant' => 'primary'])

@php
    $kelas = $variant === 'primary'
        ? 'bg-indigo-600 text-white hover:bg-indigo-700 border-transparent'
        : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300';
@endphp

<a {{ $attributes->merge(['class' => "inline-flex items-center rounded-md border px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition $kelas"]) }}>
    {{ $slot }}
</a>
