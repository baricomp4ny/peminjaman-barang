<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Keterangan perbaikan wajib diisi (mis. "Elemen pemanas diganti teknisi"). */
class PerbaikanSelesaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['keterangan' => ['required', 'string', 'min:5', 'max:500']];
    }

    public function attributes(): array
    {
        return ['keterangan' => 'keterangan perbaikan'];
    }
}
