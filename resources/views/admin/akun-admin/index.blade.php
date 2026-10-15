<x-app-layout>
    <x-admin.judul judul="Data Admin">
        <x-link-button :href="route('admin.akun-admin.create')">+ Tambah Admin</x-link-button>
    </x-admin.judul>

    <div class="rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">No. HP</th>
                        <th class="px-4 py-3">Verifikasi</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($admin as $a)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $a->name }}
                                @if ($a->is(auth()->user())) <span class="ms-1 rounded bg-indigo-100 px-1.5 text-xs text-indigo-700">Anda</span> @endif
                            </td>
                            <td class="px-4 py-3">{{ $a->email }}</td>
                            <td class="px-4 py-3">{{ $a->no_hp ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $a->verifikasi_count }} ajuan</td>
                            <td class="px-4 py-3"><x-admin.badge :value="$a->status" /></td>
                            <td class="space-x-3 whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.akun-admin.edit', $a) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Ubah</a>
                                @unless ($a->is(auth()->user()))
                                    <x-admin.hapus :action="route('admin.akun-admin.destroy', $a)" :nama="'akun admin '.$a->name" />
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs text-gray-500">Akun admin dipakai untuk login ke panel ini. Peminjam tidak memiliki akun login.</p>
</x-app-layout>
