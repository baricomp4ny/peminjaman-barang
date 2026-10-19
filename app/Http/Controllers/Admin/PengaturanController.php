<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        $nilai = collect(Pengaturan::DEFAULT)->mapWithKeys(fn ($default, $key) => [$key => Pengaturan::angka($key)]);

        return view('admin.pengaturan.edit', [
            'nilai' => $nilai,
            'syarat' => Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN),
            'templateKustom' => Pengaturan::teks(Pengaturan::TEMPLATE_SURAT) !== '',
        ]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach (collect($request->validated())->except('template_surat') as $key => $value) {
                // Pengaturan teks (syarat) disimpan apa adanya, sisanya angka
                Pengaturan::atur($key, array_key_exists($key, Pengaturan::TEKS_DEFAULT) ? trim($value) : (int) $value);
            }
        });

        // Ganti template surat bila admin mengunggah file baru
        if ($request->hasFile('template_surat')) {
            $lama = Pengaturan::teks(Pengaturan::TEMPLATE_SURAT);
            $file = $request->file('template_surat');
            // Ekstensi ditebak dari ISI file, bukan dari nama kiriman klien (cegah file .php tersimpan di folder publik)
            $ekstensi = $file->extension();
            abort_unless(in_array($ekstensi, ['pdf', 'doc', 'docx'], true), 422, 'Format template tidak didukung.');
            $path = $file->storeAs('template', 'template-surat-peminjaman.'.$ekstensi, 'public');
            if ($lama && $lama !== $path) {
                Storage::disk('public')->delete($lama);
            }
            Pengaturan::atur(Pengaturan::TEMPLATE_SURAT, $path);
        }

        return redirect()->route('admin.pengaturan.edit')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
