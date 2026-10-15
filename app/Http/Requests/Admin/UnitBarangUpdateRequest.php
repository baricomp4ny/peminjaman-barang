<?php

namespace App\Http\Requests\Admin;

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
            'lokasi_id' => ['required', 'integer', 'exists:lokasi,id'],
            'kondisi' => ['required', Rule::in([UnitBarang::KONDISI_BAIK, UnitBarang::KONDISI_RUSAK, UnitBarang::KONDISI_HILANG])],
            // Status "dipinjam" hanya diatur oleh transaksi, tidak bisa dipilih manual
            'status' => ['required', Rule::in([UnitBarang::STATUS_TERSEDIA, UnitBarang::STATUS_PERBAIKAN, UnitBarang::STATUS_HILANG])],
            'tanggal_perolehan' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var UnitBarang $unit */
                $unit = $this->route('unit');

                if ($unit->status === UnitBarang::STATUS_DIPINJAM) {
                    $validator->errors()->add('status', 'Unit sedang dipinjam. Status dan kondisi diubah lewat proses pengembalian.');

                    return;
                }

                // Unit yang rusak/hilang tidak boleh berstatus tersedia
                if ($this->input('status') === UnitBarang::STATUS_TERSEDIA && $this->input('kondisi') !== UnitBarang::KONDISI_BAIK) {
                    $validator->errors()->add('status', 'Unit dengan kondisi rusak/hilang tidak boleh berstatus tersedia.');
                }

                if ($this->input('kondisi') === UnitBarang::KONDISI_HILANG && $this->input('status') !== UnitBarang::STATUS_HILANG) {
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
