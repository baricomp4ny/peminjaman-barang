<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/** Filter halaman Jadwal Pemakaian Alat (dipakai admin & publik). */
class JadwalRequest extends FormRequest
{
    public const TAB = ['tanggal' => 'Per Tanggal', 'alat' => 'Per Alat', 'minggu' => 'Ringkasan Mingguan'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mulai' => ['nullable', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'kategori_id' => ['nullable', 'ulid', 'exists:kategori,id'],
            'lokasi_id' => ['nullable', 'ulid', 'exists:lokasi,id'],
            'barang_id' => ['nullable', 'ulid', 'exists:barang,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'tab' => ['nullable', 'in:'.implode(',', array_keys(self::TAB))],
        ];
    }

    public function mulai(): Carbon
    {
        return $this->filled('mulai') ? Carbon::parse($this->input('mulai')) : today();
    }

    public function tab(): string
    {
        return $this->input('tab', 'tanggal');
    }

    public function filter(): array
    {
        return $this->safe()->only(['kategori_id', 'lokasi_id', 'barang_id', 'q']);
    }

    public function attributes(): array
    {
        return ['mulai' => 'tanggal', 'kategori_id' => 'kategori', 'lokasi_id' => 'lokasi', 'barang_id' => 'alat'];
    }
}
