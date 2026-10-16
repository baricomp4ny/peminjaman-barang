@props(['peminjaman', 'admin' => false])

{{-- Ringkasan satu peminjaman; dipakai di halaman admin dan peminjam --}}
@php
    $p = $peminjaman;
    $terlambat = $p->isTerlambat();
@endphp

<div class="panel p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="font-mono text-sm text-marian-950/55">{{ $p->kode_peminjaman }}</div>
            <div class="mt-1 flex items-center gap-2">
                <x-admin.badge :value="$p->status" class="text-sm" />
                @if ($terlambat)
                    <span class="border border-red-600 bg-red-600 px-2 py-0.5 font-mono text-[11px] uppercase tracking-wider text-white">Terlambat {{ $p->hariTerlambatBerjalan() }} hari</span>
                @endif
            </div>
        </div>
        <div class="text-right text-sm text-marian-950/55">Diajukan {{ $p->created_at->translatedFormat('d M Y H:i') }}</div>
    </div>

    <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
        @if ($admin)
            <div>
                <dt class="text-marian-950/55">Peminjam</dt>
                <dd class="font-medium">
                    <a href="{{ route('admin.peminjam.show', $p->user_id) }}" class="hover:text-indigo-600">{{ $p->peminjam->name }}</a>
                    <span class="text-marian-950/55">({{ $p->peminjam->nomor_identitas ?? '—' }}, {{ $p->peminjam->unit ?? '—' }})</span>
                </dd>
                <dd class="text-marian-950/55">{{ $p->peminjam->email }} · {{ $p->peminjam->no_hp ?? '—' }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-marian-950/55">Tanggal</dt>
            <dd class="font-medium">
                {{ $p->tanggal_pinjam->translatedFormat('d M Y') }} s/d {{ $p->tanggal_rencana_kembali->translatedFormat('d M Y') }}
                <span class="text-marian-950/55">({{ $p->tanggal_pinjam->diffInDays($p->tanggal_rencana_kembali) }} hari)</span>
            </dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-marian-950/55">Tujuan</dt>
            <dd>{{ $p->tujuan }}</dd>
        </div>
        @if ($p->alasan_penolakan)
            <div class="sm:col-span-2 rounded-md bg-red-50 p-3">
                <dt class="font-medium text-red-800">Alasan penolakan</dt>
                <dd class="text-red-700">{{ $p->alasan_penolakan }}</dd>
            </div>
        @endif
        @if ($p->diverifikasi_pada)
            <div>
                <dt class="text-marian-950/55">Diverifikasi</dt>
                <dd>{{ $p->diverifikasi_pada->translatedFormat('d M Y H:i') }} oleh {{ $p->verifikator?->name ?? '—' }}</dd>
            </div>
        @endif
        @if ($p->diserahkan_pada)
            <div>
                <dt class="text-marian-950/55">Diserahkan</dt>
                <dd>{{ $p->diserahkan_pada->translatedFormat('d M Y H:i') }}</dd>
            </div>
        @endif
    </dl>

    <h3 class="mt-6 text-sm font-semibold text-marian-900">Barang diajukan</h3>
    <ul class="mt-2 divide-y border border-marian-950/10 text-sm">
        @foreach ($p->detail as $d)
            <li class="flex justify-between px-3 py-2">
                <span>{{ $d->barang->nama }} <span class="font-mono text-xs text-marian-950/55">{{ $d->barang->kode }}</span></span>
                <span class="font-medium">× {{ $d->jumlah }}</span>
            </li>
        @endforeach
    </ul>

    @if ($p->peminjamanUnit->isNotEmpty())
        <h3 class="mt-6 text-sm font-semibold text-marian-900">Unit diserahkan</h3>
        <div class="mt-2 overflow-x-auto rounded-2xl border border-marian-950/10">
            <table class="min-w-full divide-y text-sm">
                <thead class="bg-marian-50 text-left font-mono text-[11px] uppercase tracking-wider text-marian-950/55">
                    <tr>
                        <th class="px-3 py-2">Kode Unit</th>
                        <th class="px-3 py-2">Barang</th>
                        <th class="px-3 py-2">Dikembalikan</th>
                        <th class="px-3 py-2">Kondisi</th>
                        <th class="px-3 py-2 text-right">Terlambat</th>
                        <th class="px-3 py-2 text-right">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($p->peminjamanUnit as $u)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2 font-mono">{{ $u->unitBarang->kode_unit }}</td>
                            <td class="px-3 py-2">{{ $u->unitBarang->barang->nama }}</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ $u->dikembalikan_pada?->translatedFormat('d M Y H:i') ?? 'Belum' }}</td>
                            <td class="px-3 py-2">@if ($u->kondisi_kembali)<x-admin.badge :value="$u->kondisi_kembali" />@else — @endif</td>
                            <td class="px-3 py-2 text-right">{{ $u->hari_terlambat ? $u->hari_terlambat.' hari' : '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ $u->denda ? rupiah($u->denda) : '—' }}</td>
                        </tr>
                        @if ($u->catatan)
                            <tr><td colspan="6" class="px-3 pb-2 text-xs text-marian-950/55">Catatan: {{ $u->catatan }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
                @if ($p->peminjamanUnit->sum('denda') > 0)
                    <tfoot class="bg-marian-50 font-semibold">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-right">Total denda</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">{{ rupiah($p->peminjamanUnit->sum('denda')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    @endif

    {{ $slot }}
</div>
