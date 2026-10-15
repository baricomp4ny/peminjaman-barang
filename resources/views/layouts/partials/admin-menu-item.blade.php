@php
    // 'aktif' bisa berupa pola nama route (string) atau nilai boolean yang sudah dihitung
    $aktif = isset($item['aktif']) && (is_bool($item['aktif']) ? $item['aktif'] : request()->routeIs($item['aktif']));
@endphp

@if ($item['route'])
    <a href="{{ route($item['route'], $item['params'] ?? []) }}"
       @if ($aktif) aria-current="page" @endif
       class="flex items-center justify-between rounded-xl px-3 py-2 font-medium transition {{ $aktif ? 'bg-white text-marian-900 shadow-sm' : 'text-marian-100 hover:bg-white/10 hover:text-white' }}">
        <span>{{ $item['label'] }}</span>
        @if (! empty($item['badge']) && $jumlahAjuan > 0)
            <span class="rounded-full px-2 text-xs font-bold {{ $aktif ? 'bg-marian-800 text-white' : 'bg-white text-marian-800' }}">{{ $jumlahAjuan }}</span>
        @endif
    </a>
@else
    {{-- Fitur belum tersedia --}}
    <span class="flex cursor-not-allowed items-center justify-between rounded-xl px-3 py-2 text-marian-400" title="Segera hadir">
        <span>{{ $item['label'] }}</span>
        <span class="rounded bg-white/10 px-1.5 text-[10px] uppercase tracking-wide">segera</span>
    </span>
@endif
