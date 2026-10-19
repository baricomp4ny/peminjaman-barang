<?php

namespace App\Http\Requests\Admin;

use App\Models\Pengaturan;
use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            Pengaturan::LAMA_PINJAM_MAKS => ['required', 'integer', 'min:1', 'max:90'],
            Pengaturan::UNIT_MAKS_PER_AJUAN => ['required', 'integer', 'min:1', 'max:20'],
            Pengaturan::LAMA_PENANGGUHAN_HARI => ['required', 'integer', 'min:0', 'max:90'],
            Pengaturan::BATAS_AMBIL_HARI => ['required', 'integer', 'min:1', 'max:14'],
            Pengaturan::ESTIMASI_PENINJAUAN_HARI => ['required', 'integer', 'min:1', 'max:30'],
            Pengaturan::SYARAT_PEMINJAMAN => ['required', 'string', 'min:10', 'max:3000'],
            // Template surat baru (opsional); bila kosong, template lama tetap dipakai
            'template_surat' => ['nullable', 'file', 'mimes:docx,doc,pdf', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            Pengaturan::LAMA_PINJAM_MAKS => 'lama pinjam maksimal',
            Pengaturan::UNIT_MAKS_PER_AJUAN => 'maksimal unit per ajuan',
            Pengaturan::LAMA_PENANGGUHAN_HARI => 'lama penangguhan setelah terlambat',
            Pengaturan::BATAS_AMBIL_HARI => 'batas waktu pengambilan',
            Pengaturan::ESTIMASI_PENINJAUAN_HARI => 'estimasi peninjauan',
            'template_surat' => 'template surat',
            Pengaturan::SYARAT_PEMINJAMAN => 'syarat peminjaman',
        ];
    }
}
