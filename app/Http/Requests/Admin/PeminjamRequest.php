<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusAkun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk tambah (POST) dan ubah (PUT) data peminjam. */
class PeminjamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $peminjam = $this->route('peminjam');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($peminjam)],
            'nomor_identitas' => ['required', 'string', 'max:50', Rule::unique('users', 'nomor_identitas')->ignore($peminjam)],
            'unit' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'regex:/^[0-9+\-\s]{8,20}$/'],
            // Nonaktif = tidak bisa mengajukan peminjaman lewat halaman publik
            'status' => ['required', Rule::enum(StatusAkun::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'nomor_identitas' => 'nomor identitas',
            'no_hp' => 'no. HP',
        ];
    }

    public function messages(): array
    {
        return [
            'no_hp.regex' => 'No. HP hanya boleh berisi angka, spasi, + atau - (8–20 karakter).',
        ];
    }
}
