<?php

namespace App\Http\Requests\Peminjam;

use Illuminate\Foundation\Http\FormRequest;

/** Parameter endpoint JSON sisa unit (dipakai form ajuan publik). */
class KetersediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mulai' => ['required', 'date'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'barang' => ['required', 'array', 'max:50'],
            'barang.*' => ['ulid'],
        ];
    }
}
