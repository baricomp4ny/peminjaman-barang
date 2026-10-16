@php $edit = $akun->exists; @endphp

<x-app-layout>
    <x-admin.judul :judul="$edit ? 'Ubah Admin' : 'Tambah Admin'" :kembali="route('admin.akun-admin.index')" />

    <div class="max-w-2xl rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
        <form method="POST" action="{{ $edit ? route('admin.akun-admin.update', $akun) : route('admin.akun-admin.store') }}" class="space-y-4">
            @csrf
            @if ($edit) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nama *" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $akun->name)" required autofocus maxlength="255" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" value="Email (untuk login) *" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" :value="old('email', $akun->email)" required maxlength="255" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="no_hp" value="No. HP" />
                    <x-text-input id="no_hp" name="no_hp" class="mt-1 block w-full" :value="old('no_hp', $akun->no_hp)" maxlength="20" />
                    <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="status" value="Status *" />
                    <x-select id="status" name="status" class="mt-1 block w-full" required>
                        <option value="aktif" @selected(old('status', $akun->status?->value) === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status', $akun->status?->value) === 'nonaktif')>Nonaktif (tidak bisa login)</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>
            </div>

            <fieldset class="rounded-2xl border border-marian-100 p-4">
                <legend class="px-1 text-sm font-medium text-marian-700">{{ $edit ? 'Reset Password (kosongkan jika tidak diganti)' : 'Password *' }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" value="Password" />
                        <x-password-input id="password" name="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $edit" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                        <x-password-input id="password_confirmation" name="password_confirmation" class="mt-1 block w-full" autocomplete="new-password" :required="! $edit" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-marian-500">Minimal 8 karakter.</p>
            </fieldset>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('admin.akun-admin.index') }}" class="text-sm text-marian-600 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
