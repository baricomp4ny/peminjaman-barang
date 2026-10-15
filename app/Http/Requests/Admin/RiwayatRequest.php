<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPeminjaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filter halaman Riwayat Transaksi. */
class RiwayatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(StatusPeminjaman::class)],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ];
    }

    public function attributes(): array
    {
        return ['dari' => 'tanggal mulai', 'sampai' => 'tanggal akhir'];
    }
}
