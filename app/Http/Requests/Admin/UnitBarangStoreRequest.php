<?php

namespace App\Http\Requests\Admin;

use App\Models\UnitBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitBarangStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'barang_id' => ['required', 'integer', 'exists:barang,id'],
            'lokasi_id' => ['required', 'integer', 'exists:lokasi,id'],
            'kondisi' => ['required', Rule::in([UnitBarang::KONDISI_BAIK, UnitBarang::KONDISI_RUSAK, UnitBarang::KONDISI_HILANG])],
            'tanggal_perolehan' => ['nullable', 'date', 'before_or_equal:today'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'barang_id' => 'jenis barang',
            'lokasi_id' => 'lokasi',
            'tanggal_perolehan' => 'tanggal perolehan',
            'jumlah' => 'jumlah unit',
        ];
    }
}
