@use('App\Enums\StatusPeminjaman')
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Peminjaman {{ $p->kode_peminjaman }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #263C92; padding-bottom: 6px; margin-bottom: 12px; }
        .kop h1 { font-size: 14px; margin: 0; color: #263C92; }
        .kop p { margin: 2px 0 0; }
        h2 { font-size: 13px; text-align: center; margin: 0 0 10px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2px 4px; vertical-align: top; }
        .info td.label { width: 120px; color: #555; }
        .data th, .data td { border: 1px solid #555; padding: 4px 5px; vertical-align: top; }
        .data th { background: #e8ebf7; text-align: left; }
        .kode { font-size: 13px; font-weight: bold; letter-spacing: 1px; }
        .catatan { margin-top: 10px; font-size: 9px; color: #444; }
        .ttd td { width: 50%; text-align: center; padding-top: 6px; vertical-align: top; }
        .garis { margin-top: 60px; border-top: 1px solid #111; width: 70%; margin-left: auto; margin-right: auto; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ config('peminjaman.mitra') }}</h1>
        <p>{{ config('peminjaman.alamat') }} · {{ config('peminjaman.jam_layanan') }}</p>
    </div>

    <h2>BUKTI PEMINJAMAN ALAT</h2>

    {{-- Kode peminjaman + QR (dipindai petugas saat serah-terima) --}}
    <table>
        <tr>
            <td style="vertical-align: top;">
                <table class="info">
                    <tr><td class="label">Kode peminjaman</td><td class="kode">{{ $p->kode_peminjaman }}</td></tr>
                    <tr><td class="label">Status</td><td>{{ $p->status->label() }}</td></tr>
                    <tr><td class="label">Nama peminjam</td><td>{{ $p->peminjam->name }}</td></tr>
                    <tr><td class="label">NIM / NIP</td><td>{{ $p->peminjam->nomor_identitas ?? '-' }}</td></tr>
                    <tr><td class="label">Program studi / unit</td><td>{{ $p->peminjam->unit ?? '-' }}</td></tr>
                    <tr><td class="label">Email / No. HP</td><td>{{ $p->peminjam->email }} / {{ $p->peminjam->no_hp ?? '-' }}</td></tr>
                    <tr><td class="label">Tujuan</td><td>{{ $p->tujuan }}</td></tr>
                    <tr><td class="label">Tanggal pinjam</td><td>{{ $p->tanggal_pinjam->translatedFormat('l, d F Y') }}</td></tr>
                    <tr><td class="label">Rencana kembali</td><td>{{ $p->tanggal_rencana_kembali->translatedFormat('l, d F Y') }}</td></tr>
                    @if ($p->status === StatusPeminjaman::Disetujui)
                        <tr><td class="label">Batas pengambilan</td><td>{{ $batasAmbil->translatedFormat('d F Y') }}</td></tr>
                    @endif
                    @if ($p->diverifikasi_pada)
                        <tr><td class="label">Disetujui</td><td>{{ $p->diverifikasi_pada->translatedFormat('d F Y H:i') }} oleh {{ $p->verifikator?->name ?? '-' }}</td></tr>
                    @endif
                </table>
            </td>
            <td style="width: 140px; text-align: center; vertical-align: top;">
                <img src="{{ $qr }}" alt="QR {{ $p->kode_peminjaman }}" style="width: 130px; height: 130px;">
                <div style="font-size: 8px; color: #555;">Tunjukkan QR ini ke petugas</div>
            </td>
        </tr>
    </table>

    <p style="margin: 12px 0 4px; font-weight: bold;">Daftar barang</p>
    <table class="data">
        <thead>
            <tr><th style="width: 24px;">No</th><th>Barang</th><th style="width: 60px;">Kode</th><th style="width: 50px;">Jumlah</th><th>Unit diserahkan</th></tr>
        </thead>
        <tbody>
            @foreach ($p->detail as $i => $d)
                @php $unit = $p->peminjamanUnit->filter(fn ($u) => $u->unitBarang->barang_id === $d->barang_id)->map(fn ($u) => $u->unitBarang->kode_unit); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d->barang->nama }}</td>
                    <td>{{ $d->barang->kode }}</td>
                    <td>{{ $d->jumlah }}</td>
                    <td>{{ $unit->isNotEmpty() ? $unit->join(', ') : 'Diisi saat serah-terima: ______________' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="catatan">
        Dengan menandatangani bukti ini, peminjam menyatakan telah menerima alat dalam kondisi baik, menyetujui syarat peminjaman,
        dan bertanggung jawab atas alat sampai dikembalikan. Keterlambatan pengembalian menyebabkan penangguhan pengajuan berikutnya.
    </p>

    {{-- Kolom tanda tangan --}}
    <table class="ttd" style="margin-top: 16px;">
        <tr>
            <td>Peminjam,<div class="garis">{{ $p->peminjam->name }}</div></td>
            <td>Petugas Laboratorium,<div class="garis">(..............................................)</div></td>
        </tr>
    </table>

    <p class="catatan" style="text-align: right;">Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
</body>
</html>
