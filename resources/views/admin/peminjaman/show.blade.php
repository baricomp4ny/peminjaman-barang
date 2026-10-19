@use('App\Enums\StatusPeminjaman')
@php $p = $peminjaman; @endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-500">Transaksi</span></x-slot>

    <x-admin.judul :judul="'Peminjaman '.$p->kode_peminjaman" :kembali="url()->previous() !== url()->current() ? url()->previous() : route('admin.riwayat.index')">
        <x-link-button variant="secondary" :href="route('admin.peminjaman.bukti', $p)" target="_blank">Cetak Bukti (PDF)</x-link-button>
        @if ($p->status === StatusPeminjaman::Disetujui)
            <x-link-button variant="secondary" :href="route('admin.peminjaman.serah', $p)">Serah-terima (form)</x-link-button>
            <x-link-button :href="route('admin.scan.index', ['mode' => 'serah', 'peminjaman' => $p->id])">Serah-terima (Scan QR)</x-link-button>
        @elseif ($p->status === StatusPeminjaman::Dipinjam)
            <x-link-button variant="secondary" :href="route('admin.pengembalian.create', $p)">Pengembalian (form)</x-link-button>
            <x-link-button :href="route('admin.scan.index', ['mode' => 'kembali', 'peminjaman' => $p->id])">Pengembalian (Scan QR)</x-link-button>
        @endif
    </x-admin.judul>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-detail-peminjaman :peminjaman="$p" :admin="true" />
        </div>

        <div class="space-y-4">
            @if ($p->status === StatusPeminjaman::Menunggu)
                {{-- Panel verifikasi: identitas, ketersediaan, aturan --}}
                <div class="rounded-3xl bg-white p-5 shadow-lembut ring-1 ring-marian-100">
                    <h3 class="font-semibold text-marian-900">Verifikasi</h3>

                    <ul class="mt-3 space-y-2 text-sm">
                        <li class="flex items-start gap-2">
                            @if ($p->peminjam->isAktif()) <span class="text-marian-600">✔</span> Akun aktif
                            @else <span class="text-red-600">✖</span> Akun nonaktif @endif
                        </li>
                        <li class="flex items-start gap-2">
                            @if ($p->peminjam->nomor_identitas) <span class="text-marian-600">✔</span> Nomor identitas: {{ $p->peminjam->nomor_identitas }}
                            @else <span class="text-marian-600">!</span> Nomor identitas belum diisi @endif
                        </li>
                        <li class="flex items-start gap-2">
                            @if (! $punyaTerlambat) <span class="text-marian-600">✔</span> Tidak punya pinjaman terlambat
                            @else <span class="text-red-600">✖</span> Masih punya pinjaman terlambat @endif
                        </li>
                        <li class="flex items-start gap-2">
                            @if ($jumlahTerlambat === 0) <span class="text-marian-600">✔</span> Belum pernah terlambat
                            @else <span class="text-red-600">!</span> Riwayat terlambat {{ $jumlahTerlambat }}× @endif
                        </li>
                        @if ($penangguhan)
                            <li class="flex items-start gap-2"><span class="text-red-600">✖</span> {{ $penangguhan }}</li>
                        @endif
                    </ul>

                    <h4 class="mt-4 text-sm font-semibold text-marian-700">Ketersediaan pada rentang tanggal</h4>
                    <table class="mt-2 w-full text-sm">
                        <thead class="text-left text-xs text-marian-500"><tr><th class="py-1">Barang</th><th class="py-1 text-right">Diminta</th><th class="py-1 text-right">Sisa</th></tr></thead>
                        <tbody>
                            @foreach ($p->detail as $d)
                                @php $sisa = $stok[$d->barang_id]['sisa'] ?? 0; @endphp
                                <tr class="{{ $sisa >= $d->jumlah ? '' : 'text-red-600 font-semibold' }}">
                                    <td class="py-1">{{ $d->barang->nama }}</td>
                                    <td class="py-1 text-right">{{ $d->jumlah }}</td>
                                    <td class="py-1 text-right">{{ $sisa }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-1 text-xs text-marian-500">Sisa sudah memperhitungkan ajuan disetujui & pinjaman lain yang tanggalnya bertabrakan. Dicek ulang saat menekan Setujui.</p>

                    <div class="mt-4 flex gap-2" x-data="{ tolak: {{ $errors->tolak->any() ? 'true' : 'false' }} }">
                        <form method="POST" action="{{ route('admin.ajuan.setujui', $p) }}" onsubmit="return confirm('Setujui ajuan {{ $p->kode_peminjaman }}?');">
                            @csrf
                            <x-primary-button>Setujui</x-primary-button>
                        </form>
                        <x-danger-button type="button" @click="tolak = true">Tolak</x-danger-button>

                        {{-- Form tolak dengan alasan wajib --}}
                        <div x-show="tolak" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" style="display: none;">
                            <div @click.outside="tolak = false" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
                                <h3 class="font-semibold text-marian-900">Tolak ajuan {{ $p->kode_peminjaman }}</h3>
                                <form method="POST" action="{{ route('admin.ajuan.tolak', $p) }}" class="mt-4 space-y-3">
                                    @csrf
                                    <x-input-label for="alasan_penolakan" value="Alasan penolakan * (min. 10 karakter)" />
                                    <textarea id="alasan_penolakan" name="alasan_penolakan" rows="3" required minlength="10" maxlength="500"
                                              class="block w-full rounded-2xl border-marian-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('alasan_penolakan') }}</textarea>
                                    <x-input-error :messages="$errors->tolak->get('alasan_penolakan')" />
                                    <div class="flex justify-end gap-2">
                                        <x-secondary-button type="button" @click="tolak = false">Batal</x-secondary-button>
                                        <x-danger-button>Tolak Ajuan</x-danger-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($p->status === StatusPeminjaman::Disetujui)
                <div class="rounded-lg bg-indigo-50 p-5 text-sm text-indigo-800 shadow-lembut ring-1 ring-marian-100">
                    Siap diambil mulai <strong>{{ $p->tanggal_pinjam->translatedFormat('d M Y') }}</strong>
                    sampai <strong>{{ $batasAmbil->translatedFormat('d M Y') }}</strong>. Setelah itu otomatis kedaluwarsa.
                </div>
            @elseif ($p->isTerlambat())
                <div class="rounded-lg bg-red-50 p-5 text-sm text-red-800 shadow-lembut ring-1 ring-marian-100">
                    Terlambat <strong>{{ $p->hariTerlambatBerjalan() }} hari</strong>. Peminjam akan ditangguhkan setelah alat dikembalikan.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
