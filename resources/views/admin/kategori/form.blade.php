@php $edit = $kategori->exists; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-gray-500">Data Barang</span></x-slot>

    <x-admin.judul :judul="$edit ? 'Ubah Kategori' : 'Tambah Kategori'" :kembali="route('admin.kategori.index')" />

    <div class="max-w-xl rounded-lg bg-white p-6 shadow">
        <form method="POST" action="{{ $edit ? route('admin.kategori.update', $kategori) : route('admin.kategori.store') }}" class="space-y-4">
            @csrf
            @if ($edit) @method('PUT') @endif

            <div>
                <x-input-label for="nama" value="Nama Kategori *" />
                <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $kategori->nama)" required autofocus maxlength="100" />
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('admin.kategori.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
