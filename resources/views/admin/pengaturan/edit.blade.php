@php
    use App\Models\Pengaturan;

    // [key, label, satuan, min, max, keterangan]
    $kolom = [
        [Pengaturan::LAMA_PINJAM_MAKS, 'Lama pinjam maksimal', 'hari', 1, 90, 'Rentang tanggal pinjam sampai rencana kembali tidak boleh melebihi nilai ini.'],
        [Pengaturan::UNIT_MAKS_PER_AJUAN, 'Maksimal unit per ajuan', 'unit', 1, 20, 'Total jumlah semua barang dalam satu ajuan.'],
        [Pengaturan::LAMA_PENANGGUHAN_HARI, 'Lama penangguhan setelah terlambat', 'hari', 0, 90, 'Peminjam yang mengembalikan terlambat tidak bisa mengajukan selama sekian hari sejak tanggal pengembalian. Isi 0 jika tanpa penangguhan.'],
        [Pengaturan::ESTIMASI_PENINJAUAN_HARI, 'Estimasi peninjauan ajuan', 'hari kerja', 1, 30, 'Ditampilkan di form ajuan sebagai perkiraan lama verifikasi.'],
        [Pengaturan::BATAS_AMBIL_HARI, 'Batas waktu pengambilan', 'hari', 1, 14, 'Ajuan disetujui yang tidak diambil setelah tanggal pinjam + nilai ini menjadi kedaluwarsa.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Pengaturan</span></x-slot>

    <x-admin.judul judul="Aturan Peminjaman" />

    <div class="max-w-2xl rounded-3xl bg-white p-6 shadow-lembut ring-1 ring-marian-100">
        <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            @foreach ($kolom as [$key, $label, $satuan, $min, $maks, $ket])
                <div>
                    <x-input-label :for="$key" :value="$label.' *'" />
                    <div class="mt-1 flex items-center gap-2">
                        <x-text-input :id="$key" type="number" :name="$key" :min="$min" :max="$maks" step="1" required
                                      class="block w-40" :value="old($key, $nilai[$key])" />
                        <span class="text-sm text-marian-600">{{ $satuan }}</span>
                    </div>
                    <p class="mt-1 text-xs text-marian-500">{{ $ket }} (rentang {{ number_format($min, 0, ',', '.') }}–{{ number_format($maks, 0, ',', '.') }})</p>
                    <x-input-error :messages="$errors->get($key)" class="mt-2" />
                </div>
            @endforeach

            <div>
                <x-input-label :for="Pengaturan::SYARAT_PEMINJAMAN" value="Syarat peminjaman *" />
                <textarea id="{{ Pengaturan::SYARAT_PEMINJAMAN }}" name="{{ Pengaturan::SYARAT_PEMINJAMAN }}" rows="7" maxlength="3000" required
                          class="mt-1 block w-full rounded-xl border-marian-200 text-sm text-marian-950 shadow-sm focus:border-marian-500 focus:ring-marian-500">{{ old(Pengaturan::SYARAT_PEMINJAMAN, $syarat) }}</textarea>
                <p class="mt-1 text-xs text-marian-500">Ditampilkan di form ajuan; peminjam wajib mencentang persetujuan syarat ini. Satu poin per baris.</p>
                <x-input-error :messages="$errors->get(Pengaturan::SYARAT_PEMINJAMAN)" class="mt-2" />
            </div>

            <div>
                <x-input-label for="template_surat" value="Template surat peminjaman" />
                <p class="mt-1 text-sm text-marian-700">
                    Saat ini: <strong>{{ $templateKustom ? 'template unggahan admin' : 'template bawaan' }}</strong> ·
                    <a href="{{ route('peminjam.ajuan.template') }}" class="font-semibold text-marian-800 underline">unduh</a>
                </p>
                <input id="template_surat" type="file" name="template_surat" accept=".docx,.doc,.pdf"
                       class="mt-2 block w-full text-sm text-marian-600 file:mr-4 file:rounded-full file:border-0 file:bg-marian-50 file:px-4 file:py-2 file:font-semibold file:text-marian-800 hover:file:bg-marian-100">
                <p class="mt-1 text-xs text-marian-500">Opsional. Unggah DOCX/DOC/PDF (maks 4 MB) untuk mengganti template yang diunduh peminjam.</p>
                <x-input-error :messages="$errors->get('template_surat')" class="mt-2" />
            </div>

            <div class="rounded-md bg-marian-50 p-3 text-xs text-marian-600">
                Aturan tetap: peminjam yang masih punya pinjaman terlambat tidak bisa mengajukan peminjaman baru.
            </div>

            <x-primary-button>Simpan Pengaturan</x-primary-button>
        </form>
    </div>
</x-app-layout>
