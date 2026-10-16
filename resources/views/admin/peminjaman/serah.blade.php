@php
    $p = $peminjaman;
    $dipilihLama = collect(old('kode_unit', []));
@endphp

<x-app-layout>
    <x-slot name="header"><span class="text-sm text-marian-950/55">Transaksi</span></x-slot>

    <x-admin.judul :judul="'Serah-terima '.$p->kode_peminjaman" :kembali="route('admin.peminjaman.show', $p)" />

    @if ($jendela)
        <div class="mb-4 rounded-2xl border border-marian-200 bg-marian-50 p-4 text-sm text-marian-800">{{ $jendela }}</div>
    @endif

    <div class="max-w-3xl rounded-3xl bg-white p-6 border border-marian-950/15">
        <p class="text-sm text-marian-950/65">
            Peminjam: <strong>{{ $p->peminjam->name }}</strong> ({{ $p->peminjam->nomor_identitas }}).
            Pilih unit fisik yang diserahkan. Jumlah tiap jenis harus <strong>sama persis</strong> dengan ajuan.
            <a href="{{ route('admin.scan.index', ['mode' => 'serah', 'peminjaman' => $p->id]) }}" class="font-medium text-indigo-600 hover:underline">Pakai Scan QR &rarr;</a>
        </p>

        <form method="POST" action="{{ route('admin.peminjaman.serahkan', $p) }}" class="mt-5 space-y-6">
            @csrf

            @foreach ($p->detail as $d)
                @php $pilihan = $unit[$d->barang_id] ?? collect(); @endphp
                <fieldset>
                    <legend class="text-sm font-semibold text-marian-900">{{ $d->barang->nama }} — perlu {{ $d->jumlah }} unit</legend>
                    @if ($pilihan->isEmpty())
                        <p class="mt-2 text-sm text-red-600">Tidak ada unit tersedia untuk barang ini.</p>
                    @else
                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                            @foreach ($pilihan as $u)
                                <label class="flex cursor-pointer items-center gap-2 rounded-2xl border px-3 py-2 text-sm hover:bg-marian-50">
                                    <input type="checkbox" name="kode_unit[]" value="{{ $u->kode_unit }}" class="rounded border-marian-200 text-indigo-600"
                                           @checked($dipilihLama->contains($u->kode_unit))>
                                    <span class="font-mono">{{ $u->kode_unit }}</span>
                                    <span class="text-xs text-marian-950/55">{{ $u->lokasi->nama }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach

            <div>
                <x-input-label for="kode_manual" value="Atau ketik kode unit (pisahkan dengan koma/spasi)" />
                <x-text-input id="kode_manual" name="kode_manual" class="mt-1 block w-full font-mono uppercase" :value="old('kode_manual')" placeholder="PRJ-001, PRJ-002" />
                <x-input-error :messages="$errors->get('kode_unit')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button :disabled="(bool) $jendela" class="disabled:opacity-50">Serahkan</x-primary-button>
                <a href="{{ route('admin.peminjaman.show', $p) }}" class="text-sm text-marian-950/65 hover:text-marian-900">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
