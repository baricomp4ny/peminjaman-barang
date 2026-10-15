@props(['action', 'nama'])

{{-- Tombol hapus dengan konfirmasi browser --}}
<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return confirm('Hapus {{ addslashes($nama) }}? Tindakan ini tidak bisa dibatalkan.');">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'text-sm font-medium text-red-600 hover:text-red-800']) }}>Hapus</button>
</form>
