<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required', 'string', 'max:100',
                // Saat edit, nama milik data itu sendiri tidak dianggap duplikat
                Rule::unique('kategori', 'nama')->ignore($this->route('kategori')),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama kategori'];
    }
}
