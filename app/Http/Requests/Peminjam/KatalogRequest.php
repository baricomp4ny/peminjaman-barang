<?php

namespace App\Http\Requests\Peminjam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/** Filter katalog publik: cari, kategori, lokasi, rentang tanggal, hanya tersedia. */
class KatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'kategori_id' => ['nullable', 'ulid', 'exists:kategori,id'],
            'lokasi_id' => ['nullable', 'ulid', 'exists:lokasi,id'],
            'mulai' => ['nullable', 'date', 'after_or_equal:today'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
            'tersedia' => ['nullable', 'boolean'],
        ];
    }

    /** Rentang tanggal cek sisa unit; default hari ini, selesai default = mulai. */
    public function mulai(): Carbon
    {
        return $this->filled('mulai') ? Carbon::parse($this->input('mulai')) : today();
    }

    public function selesai(): Carbon
    {
        return $this->filled('selesai') ? Carbon::parse($this->input('selesai')) : $this->mulai();
    }

    public function attributes(): array
    {
        return ['mulai' => 'tanggal pinjam', 'selesai' => 'tanggal kembali', 'kategori_id' => 'kategori', 'lokasi_id' => 'lokasi'];
    }
}
