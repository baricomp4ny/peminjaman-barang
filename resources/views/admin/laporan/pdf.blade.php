<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $laporan['judul'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .kop { text-align: center; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
        .kop h1 { font-size: 14px; margin: 0; }
        .kop h2 { font-size: 12px; margin: 4px 0 0; }
        .kop p { margin: 2px 0 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; }
        th { background: #e5e7eb; text-align: left; }
        .ringkasan { margin-top: 10px; }
        .ringkasan td { border: none; padding: 1px 6px 1px 0; }
        .ttd { margin-top: 30px; width: 220px; float: right; text-align: center; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ config('peminjaman.mitra') }}</h1>
        <h2>{{ $laporan['judul'] }}</h2>
        <p>Periode: {{ $laporan['periode'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($laporan['kolom'] as $k)
                    <th>{{ $k }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($laporan['baris'] as $baris)
                <tr>
                    @foreach ($baris as $sel)
                        <td>{{ $sel }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($laporan['kolom']) }}" style="text-align:center">Tidak ada data pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="ringkasan">
        @foreach ($laporan['ringkasan'] as $label => $nilai)
            <tr><td>{{ $label }}</td><td>: <strong>{{ $nilai }}</strong></td></tr>
        @endforeach
    </table>

    <div class="ttd">
        {{ now()->translatedFormat('d F Y') }}<br>
        Petugas,<br><br><br><br>
        ( {{ auth()->user()->name }} )
    </div>
</body>
</html>
