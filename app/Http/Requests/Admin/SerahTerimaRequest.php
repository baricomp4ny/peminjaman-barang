<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Daftar kode unit yang diserahkan; kecocokan dengan ajuan dicek di PeminjamanService. */
class SerahTerimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Gabungan dari checkbox (kode_unit[]) dan input ketik manual (dipisah koma/spasi/baris)
        $manual = preg_split('/[\s,;]+/', (string) $this->input('kode_manual'), -1, PREG_SPLIT_NO_EMPTY);
        $kode = collect($this->input('kode_unit', []))
            ->merge($manual)
            ->map(fn ($k) => strtoupper(trim((string) $k)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge(['kode_unit' => $kode]);
    }

    public function rules(): array
    {
        return [
            'kode_unit' => ['required', 'array', 'min:1'],
            'kode_unit.*' => ['string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return ['kode_unit.required' => 'Pilih atau ketik minimal satu kode unit.'];
    }
}
