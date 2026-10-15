<?php

namespace App\Http\Requests\Admin;

use App\Services\ScanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanCariRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // Batas longgar: karakter sisa scanner dibuang dulu, panjang akhir dicek di ScanService
            'kode' => ['required', 'string', 'max:500'],
            'mode' => ['required', Rule::in([ScanService::MODE_SERAH, ScanService::MODE_KEMBALI])],
        ];
    }

    public function messages(): array
    {
        return ['kode.required' => 'Kode kosong. Scan QR atau ketik kodenya.'];
    }
}
