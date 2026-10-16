<x-app-layout>
    <div class="grid items-stretch gap-6 [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
        {{-- Panel kiri bergradasi --}}
        <section class="relative overflow-hidden rounded-5xl bg-gradient-to-br from-marian-950 via-marian-800 to-marian-500 p-8 text-white">
            <div aria-hidden="true" class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
            <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/25">Tanpa login</span>
            <h1 class="mt-4 font-extrabold [font-size:clamp(1.6rem,3.4vw,2.5rem)]">Cek Status Ajuan</h1>
            <p class="mt-2 max-w-md text-sm text-marian-100">
                Lihat apakah ajuan sudah disetujui, batas waktu pengambilan, QR pengambilan, dan perkiraan denda bila terlambat.
            </p>
            <div class="mx-auto mt-6 max-w-xs"><x-ilustrasi-lab /></div>
        </section>

        {{-- Form --}}
        <section class="flex flex-col justify-center rounded-5xl bg-white p-6 shadow-lembut ring-1 ring-marian-100 sm:p-10">
            <p class="text-sm text-marian-700">
                Masukkan <strong>kode peminjaman</strong> (dikirim ke email setelah mengajukan, contoh <span class="font-mono">PJM-20261015-0001</span>)
                dan <strong>nomor identitas</strong> yang dipakai saat mengajukan.
            </p>

            <form method="POST" action="{{ route('peminjam.status.cek') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="kode" value="Kode Peminjaman" />
                    <x-text-input id="kode" name="kode" class="mt-1 block w-full font-mono uppercase" :value="old('kode')" required autofocus maxlength="30" placeholder="PJM-…" />
                    <x-input-error :messages="$errors->get('kode')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nomor_identitas" value="Nomor Identitas (NIM/NIP)" />
                    <x-text-input id="nomor_identitas" name="nomor_identitas" class="mt-1 block w-full" :value="old('nomor_identitas')" required maxlength="50" />
                    <x-input-error :messages="$errors->get('nomor_identitas')" class="mt-2" />
                </div>
                <x-primary-button class="w-full py-3">Lihat Status</x-primary-button>
            </form>

            <p class="mt-6 border-t border-marian-100 pt-4 text-sm text-marian-600">
                Belum mengajukan? <a href="{{ route('peminjam.ajuan.create') }}" class="font-bold text-marian-800 underline decoration-marian-300 underline-offset-4">Ajukan peminjaman</a>
            </p>
        </section>
    </div>
</x-app-layout>
