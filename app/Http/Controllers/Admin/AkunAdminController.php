<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Peran;
use App\Enums\StatusAkun;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AkunAdminRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/** Pengaturan > Data Admin: akun yang boleh login ke panel admin. Parameter {akunAdmin} hanya cocok dengan role admin. */
class AkunAdminController extends Controller
{
    public function index(): View
    {
        $admin = User::where('role', Peran::Admin)
            ->withCount('verifikasi')
            ->orderBy('name')
            ->get();

        return view('admin.akun-admin.index', compact('admin'));
    }

    public function create(): View
    {
        return view('admin.akun-admin.form', ['akun' => (new User)->forceFill(['status' => StatusAkun::Aktif])]);
    }

    public function store(AkunAdminRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $akun = new User(['name' => $data['name'], 'email' => $data['email'], 'no_hp' => $data['no_hp'] ?? null]);
        // role, status, password diisi eksplisit (tidak ada di $fillable)
        $akun->forceFill([
            'role' => Peran::Admin,
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('admin.akun-admin.index')->with('success', "Akun admin \"{$akun->name}\" berhasil ditambahkan.");
    }

    public function edit(User $akunAdmin): View
    {
        return view('admin.akun-admin.form', ['akun' => $akunAdmin]);
    }

    public function update(AkunAdminRequest $request, User $akunAdmin): RedirectResponse
    {
        $data = $request->validated();

        $akunAdmin->fill(['name' => $data['name'], 'email' => $data['email'], 'no_hp' => $data['no_hp'] ?? null]);
        $akunAdmin->forceFill(['status' => $data['status']]);
        if (! empty($data['password'])) {
            $akunAdmin->forceFill(['password' => Hash::make($data['password'])]);
        }
        $akunAdmin->save();

        return redirect()->route('admin.akun-admin.index')->with('success', "Akun admin \"{$akunAdmin->name}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, User $akunAdmin): RedirectResponse
    {
        if ($akunAdmin->is($request->user())) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }

        // Admin yang pernah memverifikasi ajuan disimpan demi jejak audit; cukup dinonaktifkan
        if ($akunAdmin->verifikasi()->exists()) {
            return back()->with('error', "Akun \"{$akunAdmin->name}\" pernah memverifikasi ajuan sehingga tidak bisa dihapus. Nonaktifkan saja.");
        }

        if ($akunAdmin->isAktif() && User::where('role', Peran::Admin)->where('status', StatusAkun::Aktif)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu admin aktif.');
        }

        $akunAdmin->delete();

        return redirect()->route('admin.akun-admin.index')->with('success', "Akun admin \"{$akunAdmin->name}\" berhasil dihapus.");
    }
}
