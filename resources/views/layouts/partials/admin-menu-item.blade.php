{{-- Menu aktif: garis biru tebal di kiri + latar biru sangat muda --}}
<a href="{{ route($item['route'], $item['params'] ?? []) }}"
   @if ($item['aktif']) aria-current="page" @endif
   class="flex items-center justify-between border-l-2 py-2 pr-5 transition {{ ! empty($menjorok) ? 'pl-8' : 'pl-5' }} {{ $item['aktif'] ? 'border-marian-800 bg-marian-50 font-semibold text-marian-900' : 'border-transparent text-marian-950/75 hover:border-marian-300 hover:text-marian-900' }}">
    <span>{{ $item['label'] }}</span>
    @if (! empty($item['badge']) && $jumlahAjuan > 0)
        <span class="bg-marian-800 px-1.5 font-mono text-[10px] font-semibold text-white">{{ $jumlahAjuan }}</span>
    @endif
</a>
