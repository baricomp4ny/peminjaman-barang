<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Data Peminjam</span></x-slot>

    <x-admin.judul judul="Ubah Peminjam" :kembali="route('admin.peminjam.index')" />

    <div class="max-w-2xl rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
        <form method="POST" action="{{ route('admin.peminjam.update', $peminjam) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nama Lengkap *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $peminjam->name)" required autofocus maxlength="255" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="nomor_identitas" value="Nomor Identitas (NIM/NIP) *" />
                    <x-text-input id="nomor_identitas" name="nomor_identitas" class="mt-1 block w-full" :value="old('nomor_identitas', $peminjam->nomor_identitas)" required maxlength="50" />
                    <x-input-error :messages="$errors->get('nomor_identitas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" value="Email *" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email', $peminjam->email)" required maxlength="255" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="no_hp" value="No. HP" />
                    <x-text-input id="no_hp" name="no_hp" class="mt-1 block w-full" :value="old('no_hp', $peminjam->no_hp)" maxlength="20" placeholder="08xxxxxxxxxx" />
                    <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="unit" value="Unit / Prodi" />
                    <x-text-input id="unit" name="unit" class="mt-1 block w-full" :value="old('unit', $peminjam->unit)" maxlength="100" />
                    <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" value="Status Akun *" />
                    <x-select id="status" name="status" class="mt-1 block w-full" required>
                        <option value="aktif" @selected(old('status', $peminjam->status?->value) === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status', $peminjam->status?->value) === 'nonaktif')>Nonaktif (tidak bisa mengajukan)</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>


            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('admin.peminjam.index') }}" class="text-sm text-marian-600 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
