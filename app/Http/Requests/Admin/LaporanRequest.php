<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class LaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'format' => ['nullable', 'in:web,pdf,excel'],
        ];
    }

    /** Default periode: awal bulan ini s/d hari ini. */
    public function dari(): Carbon
    {
        return $this->filled('dari') ? Carbon::parse($this->input('dari')) : now()->startOfMonth();
    }

    public function sampai(): Carbon
    {
        return $this->filled('sampai') ? Carbon::parse($this->input('sampai')) : today();
    }

    public function attributes(): array
    {
        return ['dari' => 'tanggal mulai', 'sampai' => 'tanggal akhir'];
    }
}
