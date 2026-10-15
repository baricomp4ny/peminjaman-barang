<?php

namespace App\Http\Requests\Peminjam;

use App\Models\Barang;
use App\Models\Pengaturan;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class AjuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPeminjam() ?? false;
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
            'tujuan' => ['required', 'string', 'min:5', 'max:500'],
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_rencana_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'integer', 'distinct', 'exists:barang,id'],
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

                if (app(PeminjamanService::class)->punyaPinjamanTerlambat($this->user())) {
                    $validator->errors()->add('items', 'Anda masih memiliki pinjaman yang terlambat. Kembalikan dulu sebelum mengajukan lagi.');

                    return;
                }

                $mulai = Carbon::parse($this->input('tanggal_pinjam'));
                $selesai = Carbon::parse($this->input('tanggal_rencana_kembali'));

                // Lama pinjam = selisih tanggal (tgl 1 → tgl 8 = 7 hari)
                $maksHari = (int) Pengaturan::nilai(Pengaturan::LAMA_PINJAM_MAKS);
                if ($mulai->diffInDays($selesai) > $maksHari) {
                    $validator->errors()->add('tanggal_rencana_kembali', "Lama pinjam maksimal {$maksHari} hari.");
                }

                $maksUnit = (int) Pengaturan::nilai(Pengaturan::UNIT_MAKS_PER_AJUAN);
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
                        if (array_key_exists((int) $item['barang_id'], $kurang)) {
                            $sisa = $kurang[(int) $item['barang_id']];
                            $validator->errors()->add("items.$i.jumlah", "{$nama[$item['barang_id']]} hanya tersisa {$sisa} unit pada tanggal tersebut.");
                        }
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
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
            'tanggal_pinjam.after_or_equal' => 'Tanggal pinjam tidak boleh sebelum hari ini.',
            'tanggal_rencana_kembali.after_or_equal' => 'Tanggal rencana kembali tidak boleh sebelum tanggal pinjam.',
            'items.required' => 'Pilih minimal satu barang.',
            'items.*.barang_id.distinct' => 'Barang yang sama dipilih lebih dari sekali. Gabungkan jumlahnya.',
        ];
    }
}
