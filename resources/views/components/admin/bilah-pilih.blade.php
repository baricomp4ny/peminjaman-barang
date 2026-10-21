@props(['aksi', 'sebutan' => 'data'])

{{-- Bilah aksi "pilih banyak": muncul saat ada baris tercentang. Dipakai di dalam x-data="pilihBanyak(...)". --}}
<div x-show="dipilih.length" x-transition.opacity style="display: none;"
     class="sticky top-16 z-10 flex flex-wrap items-center gap-3 border-b border-gold bg-gold/20 px-4 py-2 text-sm">
    <span class="font-semibold text-marian-900"><span x-text="dipilih.length"></span> {{ $sebutan }} dipilih</span>

    {{ $slot }}

    <form method="POST" action="{{ $aksi }}"
          @submit="if (! confirm('Hapus ' + dipilih.length + ' {{ $sebutan }}? Data yang masih dipakai/punya riwayat akan dilewati. Tindakan ini tidak bisa dibatalkan.')) $event.preventDefault()">
        @csrf
        @method('DELETE')
        <template x-for="id in dipilih" :key="id"><input type="hidden" name="id[]" :value="id"></template>
        <button type="submit" class="rounded-full bg-red-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-red-700">Hapus terpilih</button>
    </form>

    <button type="button" @click="batal()" class="text-xs font-semibold text-marian-700 hover:underline">Batal</button>
</div>
