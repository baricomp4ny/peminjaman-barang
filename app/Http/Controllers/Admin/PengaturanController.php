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
        $nilai = collect(Pengaturan::DEFAULT)->mapWithKeys(fn ($default, $key) => [$key => Pengaturan::nilai($key)]);

        return view('admin.pengaturan.edit', compact('nilai'));
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated() as $key => $value) {
                Pengaturan::atur($key, (int) $value);
            }
        });

        return redirect()->route('admin.pengaturan.edit')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
