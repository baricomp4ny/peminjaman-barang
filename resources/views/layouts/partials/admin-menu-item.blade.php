@php
    // 'aktif' bisa berupa pola nama route (string) atau nilai boolean yang sudah dihitung
    $aktif = isset($item['aktif']) && (is_bool($item['aktif']) ? $item['aktif'] : request()->routeIs($item['aktif']));
@endphp

@if ($item['route'])
    <a href="{{ route($item['route'], $item['params'] ?? []) }}"
       @if ($aktif) aria-current="page" @endif
       class="flex items-center justify-between rounded-md px-3 py-2 {{ $aktif ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' }}">
        <span>{{ $item['label'] }}</span>
        @if (! empty($item['badge']) && $jumlahAjuan > 0)
            <span class="rounded-full bg-amber-500 px-2 text-xs font-semibold text-white">{{ $jumlahAjuan }}</span>
        @endif
    </a>
@else
    {{-- Fitur belum tersedia di sprint ini --}}
    <span class="flex cursor-not-allowed items-center justify-between rounded-md px-3 py-2 text-slate-500" title="Segera hadir">
        <span>{{ $item['label'] }}</span>
        <span class="flex items-center gap-1">
            @if (! empty($item['badge']) && $jumlahAjuan > 0)
                <span class="rounded-full bg-amber-500/60 px-2 text-xs font-semibold text-white">{{ $jumlahAjuan }}</span>
            @endif
            <span class="rounded bg-slate-700 px-1.5 text-[10px] uppercase tracking-wide">segera</span>
        </span>
    </span>
@endif
