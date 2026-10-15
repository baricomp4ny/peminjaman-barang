<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusUnit;
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
            'unit.*' => ['ulid'],
            'barang_id' => ['nullable', 'ulid', 'exists:barang,id'],
            'lokasi_id' => ['nullable', 'ulid', 'exists:lokasi,id'],
            'status' => ['nullable', Rule::enum(StatusUnit::class)],
            'kolom' => ['nullable', 'integer', 'in:3,4,5'],
        ];
    }
}
