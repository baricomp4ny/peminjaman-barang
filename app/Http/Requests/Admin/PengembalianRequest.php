<?php

namespace App\Http\Requests\Admin;

use App\Models\UnitBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bentuk input: unit[<unit_barang_id>][pilih|kondisi|catatan] + tanggal_kembali.
 * Hanya baris yang dicentang (pilih=1) yang diproses.
 */
class PengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal_kembali' => ['required', 'date', 'before_or_equal:today'],
            'unit' => ['required', 'array'],
            'unit.*.pilih' => ['nullable', 'boolean'],
            'unit.*.kondisi' => ['required_if:unit.*.pilih,1', 'nullable', Rule::in([UnitBarang::KONDISI_BAIK, UnitBarang::KONDISI_RUSAK, UnitBarang::KONDISI_HILANG])],
            'unit.*.catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, array{kondisi:string, catatan:?string}> unit yang dipilih */
    public function unitDipilih(): array
    {
        return collect($this->validated('unit'))
            ->filter(fn ($u) => ! empty($u['pilih']))
            ->map(fn ($u) => ['kondisi' => $u['kondisi'], 'catatan' => $u['catatan'] ?? null])
            ->all();
    }

    public function attributes(): array
    {
        return [
            'tanggal_kembali' => 'tanggal kembali',
            'unit.*.kondisi' => 'kondisi',
            'unit.*.catatan' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_kembali.before_or_equal' => 'Tanggal kembali tidak boleh di masa depan.',
            'unit.*.kondisi.required_if' => 'Pilih kondisi untuk setiap unit yang dikembalikan.',
        ];
    }
}
