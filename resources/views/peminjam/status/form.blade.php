<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Cek Status Ajuan</h1>
    </x-slot>

    <div class="mx-auto max-w-md rounded-lg bg-white p-6 shadow">
        <p class="text-sm text-gray-600">
            Masukkan <strong>kode peminjaman</strong> (dikirim ke email Anda setelah mengajukan, contoh <span class="font-mono">PJM-20261015-0001</span>)
            dan <strong>nomor identitas</strong> yang dipakai saat mengajukan.
        </p>

        <form method="POST" action="{{ route('peminjam.status.cek') }}" class="mt-5 space-y-4">
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
            <x-primary-button>Lihat Status</x-primary-button>
        </form>

        <p class="mt-6 border-t pt-4 text-sm text-gray-500">
            Belum mengajukan? <a href="{{ route('peminjam.ajuan.create') }}" class="font-medium text-indigo-600 hover:underline">Ajukan peminjaman</a>.
        </p>
    </div>
</x-app-layout>
