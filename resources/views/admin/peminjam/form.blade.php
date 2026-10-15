@php $edit = $peminjam->exists; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Data Peminjam</span></x-slot>

    <x-admin.judul :judul="$edit ? 'Ubah Peminjam' : 'Tambah Peminjam'" :kembali="route('admin.peminjam.index')" />

    <div class="max-w-2xl rounded-lg bg-white p-6 shadow">
        <form method="POST" action="{{ $edit ? route('admin.peminjam.update', $peminjam) : route('admin.peminjam.store') }}" class="space-y-4">
            @csrf
            @if ($edit) @method('PUT') @endif

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
                        <option value="aktif" @selected(old('status', $peminjam->status) === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status', $peminjam->status) === 'nonaktif')>Nonaktif (tidak bisa login)</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <fieldset class="rounded-md border border-gray-200 p-4">
                <legend class="px-1 text-sm font-medium text-gray-700">{{ $edit ? 'Reset Password (kosongkan jika tidak diganti)' : 'Password *' }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $edit" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="mt-1 block w-full" autocomplete="new-password" :required="! $edit" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Minimal 8 karakter.</p>
            </fieldset>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('admin.peminjam.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
