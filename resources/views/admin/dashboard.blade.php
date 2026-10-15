<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
    </x-slot>

    <div class="rounded-lg bg-white p-6 shadow">
        <h2 class="text-base font-semibold text-gray-800">Selamat datang, {{ Auth::user()->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">
            Ringkasan ajuan, peminjaman aktif, keterlambatan, dan stok barang akan tampil di sini (Sprint 3).
        </p>
    </div>
</x-app-layout>
