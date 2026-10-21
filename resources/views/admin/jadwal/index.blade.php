<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Transaksi</span></x-slot>

    <x-admin.judul judul="Jadwal Pemakaian Alat" />
    <p class="-mt-2 mb-4 text-sm text-marian-600">Klik balok untuk membuka detail transaksi. Ajuan disetujui/menunggu ditampilkan pada unit kosong pertama dari jenisnya (unit pastinya ditentukan saat serah-terima).</p>

    <x-jadwal :data="$data" :mulai="$mulai" :tab="$tab" :kategori="$kategori" :lokasi="$lokasi" rute="admin.jadwal" />
</x-app-layout>
