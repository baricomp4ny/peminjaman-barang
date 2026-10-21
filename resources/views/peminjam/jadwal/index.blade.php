<x-app-layout>
    {{-- Jadwal publik: hanya status terisi/kosong, tanpa identitas peminjam --}}
    <section class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 px-6 py-8 text-white sm:px-10">
        <div aria-hidden="true" class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
        <div class="relative max-w-2xl">
            <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/25">Tanpa login</span>
            <h1 class="mt-3 font-extrabold [font-size:clamp(1.6rem,3.4vw,2.5rem)]">Jadwal Pemakaian Alat</h1>
            <p class="mt-2 text-sm text-marian-100">Lihat kapan alat sedang dipakai atau masih kosong, lalu pilih tanggal yang longgar untuk mengajukan peminjaman.</p>
        </div>
    </section>

    <div class="mt-6">
        <x-jadwal :data="$data" :mulai="$mulai" :tab="$tab" :kategori="$kategori" :lokasi="$lokasi" :publik="true" rute="peminjam.jadwal" />
    </div>
</x-app-layout>
