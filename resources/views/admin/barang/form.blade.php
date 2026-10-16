@php
    $edit = $barang->exists;
    // Kode dikunci bila sudah ada unit (label QR sudah memakai kode ini)
    $kodeTerkunci = $edit && $barang->unit()->exists();
@endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Data Barang</span></x-slot>

    <x-admin.judul :judul="$edit ? 'Ubah Jenis Barang' : 'Tambah Jenis Barang'"
                   :kembali="$edit ? route('admin.barang.show', $barang) : route('admin.barang.index')" />

    <div class="max-w-2xl rounded-3xl bg-white p-6 border border-marian-950/15">
        <form method="POST" enctype="multipart/form-data"
              action="{{ $edit ? route('admin.barang.update', $barang) : route('admin.barang.store') }}" class="space-y-4">
            @csrf
            @if ($edit) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <x-input-label for="nama" value="Nama Barang *" />
                    <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $barang->nama)" required autofocus maxlength="150" />
                    <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="kode" value="Kode Barang *" />
                    <x-text-input id="kode" name="kode" class="mt-1 block w-full font-mono uppercase" :value="old('kode', $barang->kode)"
                                  required maxlength="5" placeholder="PRJ" :readonly="$kodeTerkunci" />
                    <p class="mt-1 text-xs text-marian-950/55">
                        {{ $kodeTerkunci ? 'Terkunci karena sudah ada unit.' : '2–5 huruf. Dipakai untuk kode unit, misal PRJ-001.' }}
                    </p>
                    <x-input-error :messages="$errors->get('kode')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="kategori_id" value="Kategori *" />
                <x-select id="kategori_id" name="kategori_id" class="mt-1 block w-full" required>
                    <option value="">— Pilih kategori —</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(old('kategori_id', $barang->kategori_id) == $k->id)>{{ $k->nama }}</option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('kategori_id')" class="mt-2" />
                @if ($kategori->isEmpty())
                    <p class="mt-1 text-xs text-marian-700">Belum ada kategori. <a href="{{ route('admin.kategori.create') }}" class="underline">Tambah kategori dulu</a>.</p>
                @endif
            </div>

            <div>
                <x-input-label for="deskripsi" value="Deskripsi" />
                <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000"
                          class="mt-1 block w-full rounded-2xl border-marian-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('deskripsi', $barang->deskripsi) }}</textarea>
                <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="foto" value="Foto (jpg/png/webp, maks 2 MB)" />
                @if ($barang->foto)
                    <div class="mt-2 flex items-center gap-4">
                        <img src="{{ Storage::url($barang->foto) }}" alt="Foto {{ $barang->nama }}" class="h-20 w-20 rounded object-cover">
                        <label class="inline-flex items-center gap-2 text-sm text-marian-700">
                            <input type="checkbox" name="hapus_foto" value="1" class="rounded border-marian-200 text-red-600"> Hapus foto
                        </label>
                    </div>
                @endif
                <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                       class="mt-2 block w-full text-sm text-marian-950/65 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100">
                <x-input-error :messages="$errors->get('foto')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Simpan</x-primary-button>
                <a href="{{ $edit ? route('admin.barang.show', $barang) : route('admin.barang.index') }}" class="text-sm text-marian-950/65 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
