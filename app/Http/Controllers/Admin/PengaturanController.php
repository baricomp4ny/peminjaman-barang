<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        $nilai = collect(Pengaturan::DEFAULT)->mapWithKeys(fn ($default, $key) => [$key => Pengaturan::angka($key)]);

        return view('admin.pengaturan.edit', [
            'nilai' => $nilai,
            'syarat' => Pengaturan::teks(Pengaturan::SYARAT_PEMINJAMAN),
        ]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated() as $key => $value) {
                // Pengaturan teks (syarat) disimpan apa adanya, sisanya angka
                Pengaturan::atur($key, array_key_exists($key, Pengaturan::TEKS_DEFAULT) ? trim($value) : (int) $value);
            }
        });

        return redirect()->route('admin.pengaturan.edit')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
