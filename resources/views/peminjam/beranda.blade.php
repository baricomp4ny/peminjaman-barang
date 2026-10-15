<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-gray-800">Beranda</h1>
    </x-slot>

    <div class="rounded-lg bg-white p-6 shadow">
        <h2 class="text-base font-semibold text-gray-800">Halo, {{ Auth::user()->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">
            Status pinjaman aktif dan pengingat jatuh tempo Anda akan tampil di sini (Sprint 3).
        </p>
    </div>
</x-app-layout>
