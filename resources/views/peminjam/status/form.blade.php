<x-app-layout>
    <div class="panel grid items-stretch [grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr))]">
        {{-- Panel kiri: keterangan + gambar teknik --}}
        <section class="flex flex-col border-b border-marian-950/15 p-8 md:border-b-0 md:border-r">
            <div class="label-bagian">Pelacakan / Tanpa login</div>
            <h1 class="mt-4 font-semibold tracking-tight [font-size:clamp(1.6rem,3.4vw,2.3rem)]">Cek Status Ajuan</h1>
            <p class="mt-2 max-w-md text-sm text-marian-950/70">
                Lihat apakah ajuan sudah disetujui, batas waktu pengambilan, QR pengambilan, dan perkiraan denda bila terlambat.
            </p>
            <div class="mx-auto mt-auto w-full max-w-xs pt-6"><x-ilustrasi-lab /></div>
        </section>

        {{-- Form --}}
        <section class="flex flex-col justify-center p-6 sm:p-10">
            <p class="text-sm text-marian-950/75">
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
                    <x-text-input id="nomor_identitas" name="nomor_identitas" class="mt-1 block w-full font-mono" :value="old('nomor_identitas')" required maxlength="50" />
                    <x-input-error :messages="$errors->get('nomor_identitas')" class="mt-2" />
                </div>
                <x-primary-button class="w-full py-3">Lihat Status</x-primary-button>
            </form>

            <p class="mt-6 border-t border-marian-950/15 pt-4 text-sm text-marian-950/65">
                Belum mengajukan? <a href="{{ route('peminjam.ajuan.create') }}" class="font-semibold text-marian-800 underline decoration-marian-300 underline-offset-4">Ajukan peminjaman</a>
            </p>
        </section>
    </div>
</x-app-layout>
