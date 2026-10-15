<?php

namespace App\Http\Requests\Admin;

use App\Models\Barang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Kode selalu disimpan huruf kapital tanpa spasi
        if ($this->filled('kode')) {
            $this->merge(['kode' => strtoupper(trim($this->input('kode')))]);
        }
    }

    public function rules(): array
    {
        return [
            'kategori_id' => ['required', 'ulid', 'exists:kategori,id'],
            'kode' => ['required', 'regex:/^[A-Z]{2,5}$/', Rule::unique('barang', 'kode')->ignore($this->route('barang'))],
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var Barang|null $barang */
                $barang = $this->route('barang');

                // Kode tidak boleh diganti jika sudah ada unit: label QR yang tercetak memakai kode lama
                if ($barang && $barang->kode !== $this->input('kode') && $barang->unit()->exists()) {
                    $validator->errors()->add('kode', 'Kode tidak bisa diubah karena jenis barang ini sudah memiliki unit.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'kategori_id' => 'kategori',
            'kode' => 'kode barang',
            'nama' => 'nama barang',
        ];
    }

    public function messages(): array
    {
        return [
            'kode.regex' => 'Kode barang harus 2–5 huruf (A–Z), misal PRJ.',
        ];
    }
}
