<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Scan QR Code</span></x-slot>

    <div x-data="halamanScan(@js([
            'mode' => $mode,
            'awal' => $awal,
            'urlCari' => route('admin.scan.cari'),
            'urlCek' => route('admin.scan.cek-unit'),
            'urlSerah' => route('admin.peminjaman.serahkan', '__ID__'),
            'urlKembali' => route('admin.pengembalian.store', '__ID__'),
            'hariIni' => now()->toDateString(),
        ]))" @beforeunload.window="matikanKamera()">

        {{-- Pilihan mode --}}
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <h2 class="me-2 text-xl font-semibold text-marian-900">Scan QR</h2>
            @foreach (['serah' => 'Serah-terima', 'kembali' => 'Pengembalian'] as $nilai => $label)
                <a href="{{ route('admin.scan.index', ['mode' => $nilai]) }}"
                   class="rounded-sm px-4 py-1.5 text-sm font-medium {{ $mode === $nilai ? 'bg-indigo-600 text-white' : 'bg-white text-marian-700 shadow-sm hover:bg-marian-50' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Kiri: kamera + input manual --}}
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-3xl bg-white p-4 border border-marian-950/15">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-marian-900">Kamera</h3>
                        <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                x-text="kameraAktif ? 'Matikan kamera' : 'Nyalakan kamera'" @click="kameraAktif ? matikanKamera() : nyalakanKamera()"></button>
                    </div>
                    <div id="pembaca-qr" class="overflow-hidden rounded-md bg-gray-900" x-show="kameraAktif"></div>
                    <p class="text-sm text-marian-950/55" x-show="!kameraAktif && !pesanKamera">Kamera mati. Nyalakan untuk scan dengan kamera, atau pakai scanner USB / input manual di bawah.</p>
                    <p class="rounded-md bg-marian-50 p-3 text-sm text-marian-800" x-show="pesanKamera" x-text="pesanKamera"></p>
                </div>

                <div class="rounded-3xl bg-white p-4 border border-marian-950/15">
                    {{-- Indikator: hijau bila hasil scanner USB akan langsung terbaca --}}
                    <button type="button" @click="paksaFokus()"
                            :class="scannerSiap ? 'bg-marian-800 text-white ring-marian-800' : 'bg-marian-50 text-marian-800 ring-marian-300 animate-pulse'"
                            class="mb-3 flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-medium ring-1">
                        <span class="h-2.5 w-2.5 rounded-full" :class="scannerSiap ? 'bg-white' : 'bg-marian-400'"></span>
                        <span x-text="scannerSiap ? 'Scanner siap — arahkan scanner USB ke QR' : 'Klik di sini untuk mengaktifkan scanner'"></span>
                        {{-- x-text (bukan x-show): status sibuk bisa berganti sangat cepat --}}
                        <span class="ms-auto text-xs" x-text="sibuk ? 'memproses' + (antrean.length ? ' (+' + antrean.length + ' antre)' : '') + '…' : ''"></span>
                    </button>

                    <form @submit.prevent="kirimManual()">
                        <x-input-label for="kode_manual" value="Scanner USB / input kode manual" />
                        <div class="mt-1 flex gap-2">
                            <x-text-input id="kode_manual" x-ref="manual" x-model="manual" class="block w-full font-mono uppercase"
                                          autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false"
                                          maxlength="150" placeholder="PJM-… / PRJ-001" />
                            <x-primary-button>Proses</x-primary-button>
                        </div>
                        <p class="mt-1 text-xs text-marian-950/55">
                            Scanner USB mengetik kode lalu menekan Enter/Tab secara otomatis. Bisa juga diketik manual lalu Enter.
                            Kode yang sama dalam 2,5 detik dianggap scan ganda dan diabaikan.
                        </p>
                    </form>
                </div>

                {{-- Umpan balik scan terakhir; :key berganti tiap pesan supaya animasi kedip diputar ulang --}}
                <template x-for="p in [pesan]" :key="pesan.n">
                    <div x-show="p.teks"
                         :class="{ 'border-marian-800 bg-marian-800 text-white': p.jenis === 'ok', 'border-red-400 bg-red-50 text-red-800 kedip-gagal': p.jenis === 'gagal', 'border-marian-200 bg-marian-50 text-marian-800': p.jenis === 'info' }"
                         class="rounded-lg border-2 p-4 text-sm font-medium" role="status" aria-live="assertive">
                        <span x-text="p.teks"></span>
                        <span x-show="p.jenis === 'gagal' && p.teks.includes('tidak dikenal')" class="mt-1 block text-xs font-normal">
                            Pastikan label QR tidak rusak/kotor dan unit sudah terdaftar di Data Barang.
                        </span>
                    </div>
                </template>
            </div>

            {{-- Kanan: transaksi --}}
            <div class="lg:col-span-3">
                {{-- Belum ada transaksi --}}
                <div x-show="!transaksi && pilihan.length === 0" class="rounded-3xl bg-white p-6 border border-marian-950/15">
                    <h3 class="font-semibold text-marian-900">Langkah 1: buka transaksi</h3>
                    @if ($mode === 'serah')
                        <p class="mt-2 text-sm text-marian-950/65">Scan <strong>QR kode peminjaman</strong> dari layar/email peminjam atau ketik kodenya (PJM-…). Ajuan yang muncul hanya yang berstatus <em>disetujui</em>.</p>
                    @else
                        <p class="mt-2 text-sm text-marian-950/65">Scan <strong>QR salah satu unit</strong> yang dikembalikan atau <strong>QR kode peminjaman</strong>.</p>
                    @endif
                </div>

                {{-- Pilih transaksi bila peminjam punya lebih dari satu --}}
                <div x-show="!transaksi && pilihan.length > 0" class="rounded-3xl bg-white p-6 border border-marian-950/15">
                    <h3 class="font-semibold text-marian-900">Pilih transaksi</h3>
                    <div class="mt-3 space-y-2">
                        <template x-for="t in pilihan" :key="t.id">
                            <button type="button" @click="buka(t)" class="block w-full rounded-2xl border p-3 text-left text-sm hover:bg-marian-50">
                                <span class="font-mono" x-text="t.kode"></span> · <span x-text="t.peminjam"></span>
                                <span class="block text-marian-950/55" x-text="t.tanggal + ' — ' + t.detail.map(d => d.nama + ' ×' + d.jumlah).join(', ')"></span>
                            </button>
                        </template>
                    </div>
                    <button type="button" class="mt-3 text-sm text-marian-950/55 hover:text-marian-700" @click="reset()">Batal</button>
                </div>

                {{-- Transaksi aktif --}}
                <template x-if="transaksi">
                    <div class="rounded-3xl bg-white p-6 border border-marian-950/15">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <a :href="transaksi.url_detail" class="font-mono text-sm text-indigo-600 hover:underline" x-text="transaksi.kode"></a>
                                <div class="text-lg font-semibold text-marian-900" x-text="transaksi.peminjam"></div>
                                <div class="text-sm text-marian-950/55"><span x-text="transaksi.nomor_identitas"></span> · <span x-text="transaksi.tanggal"></span></div>
                            </div>
                            <button type="button" class="text-sm text-marian-950/55 hover:text-marian-700" @click="reset()">Ganti transaksi</button>
                        </div>

                        <p x-show="transaksi.peringatan" class="mt-3 rounded-md bg-marian-50 p-3 text-sm text-marian-800" x-text="transaksi.peringatan"></p>
                        <p x-show="transaksi.terlambat > 0" class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-800" x-text="'Terlambat ' + transaksi.terlambat + ' hari — denda dihitung otomatis saat disimpan.'"></p>

                        <h3 class="mt-5 text-sm font-semibold text-marian-900">
                            Langkah 2: scan QR setiap unit
                            <span class="font-normal text-marian-950/55" x-text="mode === 'serah' ? '(harus sesuai jenis & jumlah ajuan)' : '(boleh sebagian)'"></span>
                        </h3>

                        {{-- Progres per jenis barang (serah-terima) --}}
                        <ul class="mt-2 space-y-1 text-sm" x-show="mode === 'serah'">
                            <template x-for="d in transaksi.detail" :key="d.barang_id">
                                <li class="flex items-center justify-between rounded-2xl border px-3 py-2"
                                    :class="jumlahTerscan(d.barang_id) === d.jumlah ? 'border-marian-400 bg-marian-100' : ''">
                                    <span x-text="d.nama"></span>
                                    <span class="font-semibold" x-text="jumlahTerscan(d.barang_id) + ' / ' + d.jumlah + (jumlahTerscan(d.barang_id) === d.jumlah ? ' ✔' : '')"></span>
                                </li>
                            </template>
                        </ul>

                        {{-- Unit yang masih dipinjam (pengembalian) --}}
                        <p class="mt-2 text-sm text-marian-950/65" x-show="mode === 'kembali'">
                            Belum kembali:
                            <template x-for="u in transaksi.belum_kembali" :key="u.unit_id">
                                <span class="me-1 inline-block rounded px-1.5 font-mono text-xs"
                                      :class="sudahDipindai(u.unit_id) ? 'bg-marian-800 text-white line-through' : 'bg-marian-50'" x-text="u.kode_unit"></span>
                            </template>
                        </p>

                        {{-- Daftar hasil scan --}}
                        <div class="mt-4 divide-y border" x-show="dipindai.length > 0">
                            <template x-for="(u, i) in dipindai" :key="u.unit_id">
                                <div class="grid items-center gap-2 p-3 sm:grid-cols-12">
                                    <div class="sm:col-span-4">
                                        <span class="font-mono text-sm font-semibold" x-text="u.kode_unit"></span>
                                        <span class="block text-xs text-marian-950/55" x-text="u.nama"></span>
                                    </div>
                                    <template x-if="mode === 'kembali'">
                                        <select x-model="u.kondisi" class="rounded-2xl border-marian-200 text-sm shadow-sm sm:col-span-3" :aria-label="'Kondisi ' + u.kode_unit">
                                            <option value="baik">Baik</option>
                                            <option value="rusak">Rusak</option>
                                            <option value="hilang">Hilang</option>
                                        </select>
                                    </template>
                                    <template x-if="mode === 'kembali'">
                                        <input x-model="u.catatan" maxlength="255" placeholder="Catatan (opsional)"
                                               class="rounded-2xl border-marian-200 text-sm shadow-sm sm:col-span-4">
                                    </template>
                                    <button type="button" @click="hapus(i)" class="text-sm text-red-600 hover:text-red-800 sm:col-span-1 sm:text-right" :class="mode === 'serah' && 'sm:col-start-12'">Hapus</button>
                                </div>
                            </template>
                        </div>

                        {{-- Langkah 3: simpan (form biasa ke endpoint yang sama dengan form Sprint 3) --}}
                        <form method="POST" :action="urlSimpan()" class="mt-5 border-t pt-4" @submit="matikanKamera()">
                            @csrf
                            <input type="hidden" name="dari_scan" value="1">
                            <template x-if="mode === 'serah'">
                                <div>
                                    <template x-for="u in dipindai" :key="u.unit_id">
                                        <input type="hidden" name="kode_unit[]" :value="u.kode_unit">
                                    </template>
                                </div>
                            </template>
                            <template x-if="mode === 'kembali'">
                                <div>
                                    <input type="hidden" name="tanggal_kembali" :value="cfg.hariIni">
                                    <template x-for="u in dipindai" :key="u.unit_id">
                                        <div>
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][pilih]'" value="1">
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][kondisi]'" :value="u.kondisi">
                                            <input type="hidden" :name="'unit[' + u.unit_id + '][catatan]'" :value="u.catatan">
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <x-primary-button x-bind:disabled="!bisaSimpan()" class="disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-text="mode === 'serah' ? 'Selesaikan serah-terima' : 'Simpan pengembalian (' + dipindai.length + ' unit)'"></span>
                            </x-primary-button>
                            <p class="mt-1 text-xs text-marian-950/55" x-show="mode === 'serah' && !lengkap()">Tombol aktif setelah semua jenis barang lengkap.</p>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Library scan kamera disimpan lokal (bukan CDN) --}}
    <script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
</x-app-layout>
