@php $edit = $lokasi->exists; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Data Barang</span></x-slot>

    <x-admin.judul :judul="$edit ? 'Ubah Lokasi' : 'Tambah Lokasi'" :kembali="route('admin.lokasi.index')" />

    <div class="max-w-xl rounded-3xl bg-white p-6 border border-marian-950/15">
        <form method="POST" action="{{ $edit ? route('admin.lokasi.update', $lokasi) : route('admin.lokasi.store') }}" class="space-y-4">
            @csrf
            @if ($edit) @method('PUT') @endif

            <div>
                <x-input-label for="nama" value="Nama Lokasi *" />
                <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $lokasi->nama)" required autofocus maxlength="100" />
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="keterangan" value="Keterangan" />
                <x-text-input id="keterangan" name="keterangan" class="mt-1 block w-full" :value="old('keterangan', $lokasi->keterangan)" maxlength="255" placeholder="Misal: Lantai 2, ruang 201" />
                <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ route('admin.lokasi.index') }}" class="text-sm text-marian-950/65 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
