<?php

namespace App\Http\Requests\Peminjam;

use App\Models\Barang;
use App\Models\Pengaturan;
use App\Services\IdentitasPeminjamService;
use App\Services\KeterlambatanService;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/** Form ajuan publik: peminjam tidak login, identitas diisi di form. */
class AjuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Buang baris barang yang kosong (baris tambahan yang tidak diisi)
        $items = collect($this->input('items', []))
            ->filter(fn ($i) => is_array($i) && filled($i['barang_id'] ?? null))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            // Identitas peminjam
            'name' => ['required', 'string', 'max:255'],
            'nomor_identitas' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'unit' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            // Jebakan bot: kolom tersembunyi ini harus kosong
            'website' => ['prohibited'],

            // Ajuan
            'tujuan' => ['required', 'string', 'min:5', 'max:500'],
            // Wajib menyetujui syarat peminjaman (teks syarat diatur admin)
            'setuju_syarat' => ['accepted'],
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_rencana_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'ulid', 'distinct', 'exists:barang,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Aturan bisnis hanya dicek bila format dasarnya sudah benar
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $identitas = app(IdentitasPeminjamService::class);
                foreach ($identitas->periksa($this->only(['name', 'nomor_identitas', 'email', 'unit', 'no_hp'])) as $field => $pesan) {
                    $validator->errors()->add($field, $pesan);
                }
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // Peminjam (berdasarkan NIM) yang masih punya pinjaman terlambat tidak boleh mengajukan
                $peminjam = $identitas->cari($this->input('nomor_identitas'));
                if ($peminjam && app(PeminjamanService::class)->punyaPinjamanTerlambat($peminjam)) {
                    $validator->errors()->add('nomor_identitas', 'Anda masih memiliki pinjaman yang terlambat. Kembalikan dulu sebelum mengajukan lagi.');

                    return;
                }
                // Masa penangguhan setelah mengembalikan terlambat
                if ($peminjam && $pesan = app(KeterlambatanService::class)->pesanPenangguhan($peminjam)) {
                    $validator->errors()->add('nomor_identitas', $pesan);

                    return;
                }

                $mulai = Carbon::parse($this->input('tanggal_pinjam'));
                $selesai = Carbon::parse($this->input('tanggal_rencana_kembali'));

                // Lama pinjam = selisih tanggal (tgl 1 → tgl 8 = 7 hari)
                $maksHari = Pengaturan::angka(Pengaturan::LAMA_PINJAM_MAKS);
                if ($mulai->diffInDays($selesai) > $maksHari) {
                    $validator->errors()->add('tanggal_rencana_kembali', "Lama pinjam maksimal {$maksHari} hari.");
                }

                $maksUnit = Pengaturan::angka(Pengaturan::UNIT_MAKS_PER_AJUAN);
                $total = collect($this->input('items'))->sum('jumlah');
                if ($total > $maksUnit) {
                    $validator->errors()->add('items', "Total maksimal {$maksUnit} unit per ajuan (Anda meminta {$total}).");
                }

                // Peringatan awal ketersediaan; dicek ulang saat admin menyetujui
                $permintaan = collect($this->input('items'))->pluck('jumlah', 'barang_id')->map(fn ($j) => (int) $j)->all();
                $kurang = app(KetersediaanService::class)->kekurangan($permintaan, $mulai, $selesai);
                if ($kurang !== []) {
                    $nama = Barang::whereIn('id', array_keys($kurang))->pluck('nama', 'id');
                    foreach ($this->input('items') as $i => $item) {
                        if (array_key_exists($item['barang_id'], $kurang)) {
                            $sisa = $kurang[$item['barang_id']];
                            $validator->errors()->add("items.$i.jumlah", "{$nama[$item['barang_id']]} hanya tersisa {$sisa} unit pada tanggal tersebut.");
                        }
                    }
                }
            },
        ];
    }

    /** Data identitas peminjam. */
    public function identitas(): array
    {
        return $this->safe()->only(['name', 'nomor_identitas', 'email', 'unit', 'no_hp']);
    }

    /** Data ajuan (tanpa identitas). */
    public function ajuan(): array
    {
        return $this->safe()->only(['tujuan', 'tanggal_pinjam', 'tanggal_rencana_kembali', 'items']);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'nomor_identitas' => 'nomor identitas (NIM/NIP)',
            'no_hp' => 'no. HP',
            'unit' => 'unit/prodi',
            'tujuan' => 'tujuan peminjaman',
            'tanggal_pinjam' => 'tanggal pinjam',
            'tanggal_rencana_kembali' => 'tanggal rencana kembali',
            'items' => 'daftar barang',
            'items.*.barang_id' => 'barang',
            'items.*.jumlah' => 'jumlah',
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_identitas.regex' => 'Nomor identitas hanya boleh berisi huruf, angka, titik, atau tanda hubung.',
            'no_hp.regex' => 'No. HP hanya boleh berisi angka, spasi, + atau - (8–20 karakter).',
            'website.prohibited' => 'Ajuan tidak dapat diproses.',
            'setuju_syarat.accepted' => 'Centang persetujuan syarat peminjaman untuk mengirim ajuan.',
            'tanggal_pinjam.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            'tanggal_rencana_kembali.after_or_equal' => 'Tanggal rencana kembali tidak boleh sebelum tanggal pinjam.',
            'items.required' => 'Pilih minimal satu barang.',
            'items.*.barang_id.distinct' => 'Barang yang sama dipilih lebih dari sekali. Gabungkan jumlahnya.',
        ];
    }
}
