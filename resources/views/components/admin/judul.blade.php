@props(['judul', 'kembali' => null])

{{-- Judul halaman + tombol aksi di kanan (slot) --}}
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        @if ($kembali)
            <a href="{{ $kembali }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali</a>
        @endif
        <h2 class="text-xl font-semibold text-gray-800">{{ $judul }}</h2>
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
