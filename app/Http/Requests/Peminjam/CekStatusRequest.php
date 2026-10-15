<?php

namespace App\Http\Requests\Peminjam;

use Illuminate\Foundation\Http\FormRequest;

class CekStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => strtoupper(trim((string) $this->input('kode'))),
            'nomor_identitas' => trim((string) $this->input('nomor_identitas')),
        ]);
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:30'],
            'nomor_identitas' => ['required', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return ['kode' => 'kode peminjaman', 'nomor_identitas' => 'nomor identitas (NIM/NIP)'];
    }
}
