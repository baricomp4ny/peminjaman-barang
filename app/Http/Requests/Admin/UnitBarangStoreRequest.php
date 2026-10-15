<?php

namespace App\Http\Requests\Admin;

use App\Enums\KondisiUnit;
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
            'barang_id' => ['required', 'ulid', 'exists:barang,id'],
            'lokasi_id' => ['required', 'ulid', 'exists:lokasi,id'],
            'kondisi' => ['required', Rule::enum(KondisiUnit::class)],
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
