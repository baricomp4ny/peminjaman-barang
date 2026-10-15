<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TolakRequest extends FormRequest
{
    /** Formulir tolak ada di halaman detail; bila gagal validasi, kembali ke sana dengan modal terbuka. */
    protected $errorBag = 'tolak';

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['alasan_penolakan' => 'alasan penolakan'];
    }
}
