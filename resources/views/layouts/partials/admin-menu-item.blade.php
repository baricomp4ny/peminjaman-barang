<a href="{{ route($item['route'], $item['params'] ?? []) }}"
   @if ($item['aktif']) aria-current="page" @endif
   class="flex items-center justify-between rounded-xl px-3 py-2 font-medium transition {{ $item['aktif'] ? 'bg-white text-marian-900 shadow-sm' : 'text-marian-100 hover:bg-white/10 hover:text-white' }}">
    <span>{{ $item['label'] }}</span>
    @if (! empty($item['badge']) && $jumlahAjuan > 0)
        <span class="rounded-full px-2 text-xs font-bold {{ $item['aktif'] ? 'bg-marian-800 text-white' : 'bg-white text-marian-800' }}">{{ $jumlahAjuan }}</span>
    @endif
</a>
