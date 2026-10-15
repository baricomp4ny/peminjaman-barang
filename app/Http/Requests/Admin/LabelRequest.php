<?php

namespace App\Http\Requests\Admin;

use App\Models\UnitBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filter cetak label: pilihan centang (unit[]) atau filter barang/lokasi/status. */
class LabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'unit' => ['nullable', 'array', 'max:500'],
            'unit.*' => ['integer'],
            'barang_id' => ['nullable', 'integer', 'exists:barang,id'],
            'lokasi_id' => ['nullable', 'integer', 'exists:lokasi,id'],
            'status' => ['nullable', Rule::in([UnitBarang::STATUS_TERSEDIA, UnitBarang::STATUS_DIPINJAM, UnitBarang::STATUS_PERBAIKAN, UnitBarang::STATUS_HILANG])],
            'kolom' => ['nullable', 'integer', 'in:3,4,5'],
        ];
    }
}
