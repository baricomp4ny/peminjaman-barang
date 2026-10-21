<?php

namespace App\Http\Requests\Admin;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use App\Models\UnitBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Kode unit dan jenis barang sengaja tidak bisa diubah (sudah tercetak di label QR). */
class UnitBarangUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'lokasi_id' => ['required', 'ulid', 'exists:lokasi,id'],
            'kondisi' => ['required', Rule::enum(KondisiUnit::class)],
            // Status "dipinjam" hanya diatur oleh transaksi, tidak bisa dipilih manual
            'status' => ['required', Rule::in(array_column(StatusUnit::pilihanManual(), 'value'))],
            'tanggal_perolehan' => ['nullable', 'date', 'before_or_equal:today'],
            // Alasan perubahan kondisi/status (masuk riwayat kondisi unit)
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var UnitBarang $unit */
                $unit = $this->route('unit');

                if ($unit->status === StatusUnit::Dipinjam) {
                    $validator->errors()->add('status', 'Unit sedang dipinjam. Status dan kondisi diubah lewat proses pengembalian.');

                    return;
                }

                // Unit yang rusak/hilang tidak boleh berstatus tersedia
                if ($this->input('status') === StatusUnit::Tersedia->value && $this->input('kondisi') !== KondisiUnit::Baik->value) {
                    $validator->errors()->add('status', 'Unit dengan kondisi rusak/hilang tidak boleh berstatus tersedia.');
                }

                if ($this->input('kondisi') === KondisiUnit::Hilang->value && $this->input('status') !== StatusUnit::Hilang->value) {
                    $validator->errors()->add('status', 'Unit dengan kondisi hilang harus berstatus hilang.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'lokasi_id' => 'lokasi',
            'tanggal_perolehan' => 'tanggal perolehan',
        ];
    }
}
